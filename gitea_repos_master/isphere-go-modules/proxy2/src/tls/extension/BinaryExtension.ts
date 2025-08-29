import { ExtensionType } from '../contract/ExtensionType'
import { Extension } from './Extension'

export class BinaryExtension implements Extension {
    type: ExtensionType
    buffer: Buffer

    constructor(type: ExtensionType) {
        this.type = type
    }

    marshal(): Buffer {
        return this.buffer
    }

    unmarshal(buffer: Buffer): void {
        this.buffer = buffer
    }
}
