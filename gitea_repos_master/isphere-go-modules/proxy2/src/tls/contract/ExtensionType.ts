export enum ExtensionTypeCode {
    ServerName = 0x0000, // 0
    StatusRequest = 0x0005, // 5
    SupportedGroups = 0x000a, // 10
    SupportedPoints = 0x000b, // 11
    SignatureAlgorithms = 0x000d, // 13
    ALPN = 0x0010, // 16
    StatusRequestV2 = 0x0011, // 17
    SCT = 0x0012, // 18
    ExtendedMasterSecret = 0x0017, // 23
    DelegatedCredentials = 0x0022, // 34
    SessionTicket = 0x0023, // 35
    PreSharedKey = 0x0029, // 41
    EarlyData = 0x002a, // 42
    SupportedVersions = 0x002b, // 43
    Cookie = 0x002c, // 44
    PSKModes = 0x002d, // 45
    CertificateAuthorities = 0x002f, // 47
    SignatureAlgorithmsCert = 0x0032, // 50
    KeyShare = 0x0033, // 51
    QUICTransportParameters = 0x0039, // 57
    RenegotiationInfo = 0xff01, // 65281
}

export class ExtensionType {
    code: ExtensionTypeCode
    name: string

    constructor(code: ExtensionTypeCode) {
        this.code = code
        this.name = ExtensionTypeCode[code]
    }
}