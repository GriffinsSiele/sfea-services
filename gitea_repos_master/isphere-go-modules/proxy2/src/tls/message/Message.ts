import * as Buffer from 'buffer'
import { MessageType, MessageTypeCode } from '../contract/MessageType'
import Marshaller from '../serializer/Marshaller'
import Unmarshaller from '../serializer/Unmarshaller'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import Uint8ArrayWriter from '../util/Uint8ArrayWriter'
import { unmarshalClientHello } from './ClientHello'
import { unmarshalServerHello } from './ServerHello'

export default class Message implements Marshaller, Unmarshaller {
    type: MessageType
    data: Marshaller

    marshal(): Buffer {
        const writer: Uint8ArrayWriter = new Uint8ArrayWriter()

        writer.writeByte(this.type.code)
        writer.writeByte(0x00)

        if (this.data !== undefined) {
            const data: Buffer = this.data.marshal()

            writer.writeUint16BE(data.length)
            writer.write(data)
        }

        return writer.toBuffer()
    }

    unmarshal(data: Buffer) {
        const reader: Uint8ArrayReader = new Uint8ArrayReader(data)

        this.type = new MessageType(reader.readByte())

        console.assert(reader.readByte() === 0x00, 'Reserved byte must be 0x00')

        const dataReader: Uint8ArrayReader = new Uint8ArrayReader(reader.readBytesWithUint16BEPrefixedLength())

        switch (this.type.code) {
            case MessageTypeCode.ClientHello:
                this.data = unmarshalClientHello(dataReader)
                break

            case MessageTypeCode.ServerHello:
                this.data = unmarshalServerHello(dataReader)
                break

            default:
                throw new Error('Unknown message type')
        }
    }
}
