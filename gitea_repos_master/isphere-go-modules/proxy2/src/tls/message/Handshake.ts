import { unmarshalType } from '../../util/unmarshaller'
import Marshaller from '../serializer/Marshaller'
import Unmarshaller from '../serializer/Unmarshaller'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import Uint8ArrayWriter from '../util/Uint8ArrayWriter'
import Message from './Message'

export default class Handshake implements Marshaller, Unmarshaller {
    message: Message

    marshal(): Buffer {
        const writer: Uint8ArrayWriter = new Uint8ArrayWriter()
        const message: Buffer = this.message.marshal()
        writer.writeBytesWithUint16BEPrefixedLength(message)
        return writer.toBuffer()
    }

    unmarshal(data: Buffer) {
        const reader: Uint8ArrayReader = new Uint8ArrayReader(data)
        this.message = unmarshalType(Message, reader.readBytesWithUint16BEPrefixedLength())
    }
}
