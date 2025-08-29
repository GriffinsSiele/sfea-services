import { ExtensionType, ExtensionTypeCode } from '../contract/ExtensionType'
import { BinaryExtension } from './BinaryExtension'
import { Extension } from './Extension'
import { ServerNameExtension } from './ServerNameExtension'

// @see https://www.iana.org/assignments/tls-extensiontype-values/tls-extensiontype-values.xhtml
export default function extensionFactory(type: ExtensionType, data: Buffer): Extension {
    let extension: Extension

    switch (type.code) {
        case ExtensionTypeCode.ServerName:
            extension = new ServerNameExtension()
            break
        default:
            extension = new BinaryExtension(type)
            break
    }

    extension.unmarshal(data)

    return extension
}
