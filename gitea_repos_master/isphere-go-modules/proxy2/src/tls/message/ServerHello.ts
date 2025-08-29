import { CipherSuite } from '../contract/CipherSuite'
import { CompressionMethod } from '../contract/CompressionMethod'
import { TLSVersion } from '../contract/TLSVersion'
import newExtensions, { Extension } from '../extension/Extension'
import Marshaller from '../serializer/Marshaller'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import Uint8ArrayWriter from '../util/Uint8ArrayWriter'

export class ServerHello implements Marshaller {
    public serverVersion: TLSVersion
    public serverRandom: Buffer
    public sessionId: Uint8Array
    public cipherSuite: CipherSuite
    public compressionMethod: CompressionMethod
    public extensions: Extension[]

    marshal(): Buffer {
        const writer: Uint8ArrayWriter = new Uint8ArrayWriter()

        writer.writeUint16BE(this.serverVersion.code)
        writer.write(this.serverRandom)
        writer.writeByte(this.sessionId.length)
        writer.write(Buffer.from(this.sessionId))
        writer.writeUint16BE(this.cipherSuite.code)
        writer.writeByte(this.compressionMethod.code)

        const extensions: Buffer = this.marshalExtensions()

        writer.writeUint16BE(extensions.length)
        writer.write(extensions)

        return writer.toBuffer()
    }

    marshalExtensions(): Buffer {
        const writer: Uint8ArrayWriter = new Uint8ArrayWriter()

        for (const extension of this.extensions) {
            writer.writeUint16BE(extension.type.code)

            const data = extension.marshal()

            writer.writeUint16BE(data.length)
            writer.write(data)
        }

        return writer.toBuffer()
    }
}

export function unmarshalServerHello(reader: Uint8ArrayReader): ServerHello {
    const serverHello: ServerHello = new ServerHello()

    serverHello.serverVersion = new TLSVersion(reader.readUint16BE())
    serverHello.serverRandom = reader.readBytes(32)
    serverHello.sessionId = reader.readBytesWithUint8PrefixedLength()
    serverHello.cipherSuite = new CipherSuite(reader.readUint16BE())
    serverHello.compressionMethod = new CompressionMethod(reader.readByte())

    serverHello.extensions = newExtensions(reader.readBytesWithUint16BEPrefixedLength())

    return serverHello
}