import { RecordType, RecordTypeCode } from '../contract/RecordType'
import { TLSVersion } from '../contract/TLSVersion'
import Marshaller from '../serializer/Marshaller'
import Unmarshaller from '../serializer/Unmarshaller'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import Uint8ArrayWriter from '../util/Uint8ArrayWriter'
import Handshake from './Handshake'

export default class Record implements Marshaller, Unmarshaller {
    type: RecordType
    protocolVersion?: TLSVersion
    record?: Marshaller
    buffer?: Buffer

    marshal(): Buffer {
        const writer: Uint8ArrayWriter = new Uint8ArrayWriter()

        writer.writeByte(this.type.code)

        if (this.protocolVersion !== undefined) {
            writer.writeUint16BE(this.protocolVersion.code)
        }

        if (this.record !== undefined) {
            writer.write(this.record.marshal())
        } else if (this.buffer !== undefined) {
            writer.write(this.buffer)
        } else {
            throw new Error('record or buffer must be defined')
        }

        return writer.toBuffer()
    }

    unmarshal(data: Buffer): void {
        const reader: Uint8ArrayReader = new Uint8ArrayReader(data)

        this.type = new RecordType(reader.readByte())

        switch (this.type.code) {
            case RecordTypeCode.Handshake:
                this.protocolVersion = new TLSVersion(reader.readUint16BE())
                const handshake: Handshake = new Handshake()
                handshake.unmarshal(reader.readTail())
                this.record = handshake
                break

            default:
                this.buffer = reader.readTail()
                break
        }
    }
}