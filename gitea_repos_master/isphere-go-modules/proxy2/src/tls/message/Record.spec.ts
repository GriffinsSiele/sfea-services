import { expect } from 'chai'

import { TLSVersionCode } from '../contract/TLSVersion'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import { Record, unmarshalRecord } from './Record'

describe("message/Record", (): void => {
    it("marshaller and unmarshaller should be symmetric", (): void => {
        const tlsVersionBuf: Buffer = Buffer.alloc(2)

        tlsVersionBuf.writeUint16BE(TLSVersionCode.VersionTLS12)

        const data: Buffer = Buffer.from([
            0xFF, // type
            tlsVersionBuf[0], tlsVersionBuf[1], // tls version
            0x00, 0x01, // payload length
            0x00, // payload
        ])

        const record: Record = unmarshalRecord(new Uint8ArrayReader(data))
        const marshalled: Buffer = record.marshal()

        expect(marshalled).to.deep.equal(data)
    })
})
