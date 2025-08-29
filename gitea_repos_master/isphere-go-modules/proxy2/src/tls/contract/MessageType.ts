export enum MessageTypeCode {
    HelloRequest = 0,
    ClientHello = 1,
    ServerHello = 2,
    NewSessionTicket = 4,
    EndOfEarlyData = 5,
    EncryptedExtensions = 8,
    Certificate = 11,
    ServerKeyExchange = 12,
    CertificateRequest = 13,
    ServerHelloDone = 14,
    CertificateVerify = 15,
    ClientKeyExchange = 16,
    Finished = 20,
    CertificateStatus = 22,
    KeyUpdate = 24,
    NextProtocol = 67,
    MessageHash = 254,
}

export class MessageType {
    code: MessageTypeCode
    name: string

    constructor(code: MessageTypeCode) {
        this.code = code
        this.name = MessageTypeCode[code]
    }
}