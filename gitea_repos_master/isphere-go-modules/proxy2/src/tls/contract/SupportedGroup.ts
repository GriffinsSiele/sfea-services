// @see https://www.rfc-editor.org/rfc/rfc4492#section-5.1.1
export enum SupportedGroupCode {
    // deprecated
    Sect163k1 = 0x0001, // 1
    Sect163r1 = 0x0002, // 2
    Sect163r2 = 0x0003, // 3
    Sect193r1 = 0x0004, // 4
    Sect193r2 = 0x0005, // 5
    Sect233k1 = 0x0006, // 6
    Sect233r1 = 0x0007, // 7
    Sect239k1 = 0x0008, // 8
    Sect283k1 = 0x0009, // 9
    Sect283r1 = 0x000A, // 10
    Sect409k1 = 0x000B, // 11
    Sect409r1 = 0x000C, // 12
    Sect571k1 = 0x000D, // 13
    Sect571r1 = 0x000E, // 14
    Secp160k1 = 0x000F, // 15
    Secp160r1 = 0x0010, // 16
    Secp160r2 = 0x0011, // 17
    Secp192k1 = 0x0012, // 18
    Secp192r1 = 0x0013, // 19
    Secp224k1 = 0x0014, // 20
    Secp224r1 = 0x0015, // 21
    Secp256k1 = 0x0016, // 22

    // actual
    Secp256r1 = 0x0017, // 23
    Secp384r1 = 0x0018, // 24
    Secp521r1 = 0x0019, // 25
    X25519 = 0x001D, // 29
    X448 = 0x001E, // 30
}

export class SupportedGroup {
    code: SupportedGroupCode
    name: string

    constructor(code: SupportedGroupCode) {
        this.code = code
        this.name = SupportedGroupCode[code]
    }
}