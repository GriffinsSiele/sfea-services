import { expect } from 'chai'
import * as crypto from 'crypto'

import { CipherSuiteCode } from '../contract/CipherSuite'
import { CompressionMethodCode } from '../contract/CompressionMethod'
import { TLSVersionCode } from '../contract/TLSVersion'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import { ServerHello, unmarshalServerHello } from './ServerHello'

describe("message/ServerHello", (): void => {
    it("marshaller and unmarshaller should be symmetric", (): void => {
        const tlsVersionBuf: Buffer = Buffer.alloc(2)

        tlsVersionBuf.writeUint16BE(TLSVersionCode.VersionTLS12)

        const bufferData = [
            tlsVersionBuf[0], tlsVersionBuf[1], // server version
        ]

        const serverRandom: Buffer = crypto.randomBytes(32)
        for (let i = 0; i < serverRandom.length; i++) {
            bufferData.push(serverRandom[i])  // server random
        }

        bufferData.push(0x01) // session id length
        bufferData.push(0x01) // session id

        const cipherSuiteBuf: Buffer = Buffer.alloc(2)
        cipherSuiteBuf.writeUint16BE(CipherSuiteCode.TLS_AEGIS_128L_SHA256)
        bufferData.push(cipherSuiteBuf[0], tlsVersionBuf[1]) // selected cipher suite

        bufferData.push(CompressionMethodCode.CompressionMethodNode) // selected compression method

        bufferData.push(0x00, 0x05) // extensions length
        bufferData.push(0xFF, 0xFF) // extension type
        bufferData.push(0x00, 0x01) // extension length
        bufferData.push(0x00) // extension

        const data = Buffer.from(bufferData)
        const serverHello: ServerHello = unmarshalServerHello(new Uint8ArrayReader(data))
        const marshalled: Buffer = serverHello.marshal()

        expect(marshalled).to.deep.equal(data)
    })
})
