

export enum TLSVersionCode {
    VersionTLS10 = 0x0301, // 769
    VersionTLS11 = 0x0302, // 770
    VersionTLS12 = 0x0303, // 771
    VersionTLS13 = 0x0304, // 772
    VersionSSL30 = 0x0300, // 768
}

export class TLSVersion {
    code: TLSVersionCode
    name: string

    constructor(code: TLSVersionCode) {
        this.code = code
        this.name = TLSVersionCode[code]
    }
}