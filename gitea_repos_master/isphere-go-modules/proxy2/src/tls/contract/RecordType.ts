export enum RecordTypeCode {
    ChangeCipherSpec = 0x14, // 20
    Alert = 0x15, // 21
    Handshake = 0x16, // 22,
    ApplicationData = 0x17, // 23
}

export class RecordType {
    code: RecordTypeCode
    name: string

    constructor(code: RecordTypeCode) {
        this.code = code
        this.name = RecordTypeCode[code]
    }
}
