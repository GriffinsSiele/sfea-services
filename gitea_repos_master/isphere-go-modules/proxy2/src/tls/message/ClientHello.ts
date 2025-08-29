import { CipherSuite } from '../contract/CipherSuite'
import { CompressionMethod } from '../contract/CompressionMethod'
import { TLSVersion } from '../contract/TLSVersion'
import newExtensions, { Extension } from '../extension/Extension'
import Marshaller from '../serializer/Marshaller'
import Uint16ArrayFromUint8Array, {
    Uint8ArrayFromUint16Array
} from '../util/Uint16ArrayFromUint8Array'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import Uint8ArrayWriter from '../util/Uint8ArrayWriter'

export class ClientHello implements Marshaller {
    public clientVersion: TLSVersion
    public clientRandom: Buffer
    public sessionId: Uint8Array
    public cipherSuites: CipherSuite[]
    public compressionMethods: CompressionMethod[]
    public extensions: Extension[]

    marshal(): Buffer {
        const writer: Uint8ArrayWriter = new Uint8ArrayWriter()

        writer.writeUint16BE(this.clientVersion.code)
        writer.write(this.clientRandom)
        writer.writeByte(this.sessionId.length)
        writer.write(Buffer.from(this.sessionId))

        const cipherSuitesUint16Array: Uint16Array = new Uint16Array(this.cipherSuites.length)
        for (let i: number = 0; i < this.cipherSuites.length; i++) {
            cipherSuitesUint16Array[i] = this.cipherSuites[i].code
        }

        const cipherSuites: Uint8Array = Uint8ArrayFromUint16Array(cipherSuitesUint16Array)
        writer.writeUint16BE(cipherSuites.length)
        writer.write(Buffer.from(cipherSuites))

        const compressionMethods: Uint8Array = new Uint8Array(this.compressionMethods.length)
        for (let i: number = 0; i < this.compressionMethods.length; i++) {
            compressionMethods[i] = this.compressionMethods[i].code
        }
        writer.writeByte(compressionMethods.length)
        writer.write(Buffer.from(compressionMethods))

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

export function unmarshalClientHello(reader: Uint8ArrayReader): ClientHello {
    const clientHello: ClientHello = new ClientHello()

    clientHello.clientVersion = new TLSVersion(reader.readUint16BE())
    clientHello.clientRandom = reader.readBytes(32)
    clientHello.sessionId = reader.readBytesWithUint8PrefixedLength()

    const cipherSuites: Uint16Array = Uint16ArrayFromUint8Array(reader.readBytesWithUint16BEPrefixedLength())
    clientHello.cipherSuites = []
    for (let i: number = 0; i < cipherSuites.length; i++) {
        clientHello.cipherSuites[i] = new CipherSuite(cipherSuites[i])
    }

    const compressionMethods: Uint8Array = reader.readBytesWithUint8PrefixedLength()
    clientHello.compressionMethods = []
    for (let i: number = 0; i < compressionMethods.length; i++) {
        clientHello.compressionMethods[i] = new CompressionMethod(compressionMethods[i])
    }

    clientHello.extensions = newExtensions(reader.readBytesWithUint16BEPrefixedLength())

    return clientHello
}