import { ExtensionType } from '../contract/ExtensionType'
import Marshaller from '../serializer/Marshaller'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import extensionFactory from './ExtensionFactory'

export interface Extension extends Marshaller {
    type: ExtensionType

    [key: string]: any

    marshal(): Buffer
    unmarshal(buffer: Buffer): void
}

export default function newExtensions(data: Buffer): Extension[] {
    const reader: Uint8ArrayReader = new Uint8ArrayReader(data)
    const extensions: Extension[] = []

    while (reader.hasMoreData()) {
        const type: ExtensionType = new ExtensionType(reader.readUint16BE())
        extensions.push(extensionFactory(type, reader.readBytesWithUint16BEPrefixedLength()))
    }

    return extensions
}