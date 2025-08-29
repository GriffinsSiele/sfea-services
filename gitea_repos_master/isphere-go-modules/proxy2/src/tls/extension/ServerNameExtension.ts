import assert from 'assert'

import { ExtensionType, ExtensionTypeCode } from '../contract/ExtensionType'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import Uint8ArrayWriter from '../util/Uint8ArrayWriter'
import { Extension } from './Extension'

// @see https://www.rfc-editor.org/rfc/rfc6066.html
export class ServerNameExtension implements Extension {
    type: ExtensionType = new ExtensionType(ExtensionTypeCode.ServerName)
    serverName: string

    constructor() { }

    marshal(): Buffer {
        const serverNameWriter = new Uint8ArrayWriter()
        const serverName = Buffer.from(this.serverName)
        serverNameWriter.writeByte(0x00)
        serverNameWriter.writeUint16BE(serverName.length)
        serverNameWriter.write(serverName)
        const serverNameBuffer = serverNameWriter.toBuffer()

        const writer = new Uint8ArrayWriter()
        writer.writeUint16BE(serverNameBuffer.length)
        writer.write(serverNameBuffer)
        return writer.toBuffer()
    }

    unmarshal(buffer: Buffer): void {
        const reader: Uint8ArrayReader = new Uint8ArrayReader(buffer)
        const serverNameList: Buffer = reader.readBytesWithUint16BEPrefixedLength()
        const serverNameReader: Uint8ArrayReader = new Uint8ArrayReader(serverNameList)
        const nameType: number = serverNameReader.readByte()
        assert(nameType === 0x00) // must be 0x00
        this.serverName = serverNameReader.readBytesWithUint16BEPrefixedLength().toString()
    }
}