export enum CompressionMethodCode {
    CompressionMethodNode = 0x00
}

export class CompressionMethod {
    code: CompressionMethodCode
    name: string

    constructor(code: CompressionMethodCode) {
        this.code = code
        this.name = CompressionMethodCode[code]
    }
}
