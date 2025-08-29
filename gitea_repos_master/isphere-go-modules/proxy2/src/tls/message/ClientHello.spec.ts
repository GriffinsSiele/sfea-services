import { expect } from 'chai'
import crypto from 'crypto'

import { CipherSuiteCode } from '../contract/CipherSuite'
import { CompressionMethodCode } from '../contract/CompressionMethod'
import { TLSVersionCode } from '../contract/TLSVersion'
import Uint8ArrayReader from '../util/Uint8ArrayReader'
import { ClientHello, unmarshalClientHello } from './ClientHello'

describe("message/ClientHello", (): void => {
    it("marshaller and unmarshaller should be symmetric", (): void => {
        const tlsVersionBuf: Buffer = Buffer.alloc(2)

        tlsVersionBuf.writeUint16BE(TLSVersionCode.VersionTLS12)

        const bufferData = [
            tlsVersionBuf[0], tlsVersionBuf[1], // client version
        ]

        const clientRandom: Buffer = crypto.randomBytes(32)
        for (let i = 0; i < clientRandom.length; i++) {
            bufferData.push(clientRandom[i])  // client random
        }

        bufferData.push(0x01) // session id length
        bufferData.push(0x01) // session id

        bufferData.push(0x00, 0x02) // cipher suites length
        const cipherSuiteBuf: Buffer = Buffer.alloc(2)
        cipherSuiteBuf.writeUint16BE(CipherSuiteCode.TLS_AEGIS_128L_SHA256)
        bufferData.push(cipherSuiteBuf[0], tlsVersionBuf[1])

        bufferData.push(0x01) // compression methods length
        bufferData.push(CompressionMethodCode.CompressionMethodNode)

        bufferData.push(0x00, 0x05) // extensions length
        bufferData.push(0xFF, 0xFF) // extension type
        bufferData.push(0x00, 0x01) // extension length
        bufferData.push(0x00) // extension

        const data = Buffer.from(bufferData)
        const clientHello: ClientHello = unmarshalClientHello(new Uint8ArrayReader(data))
        const marshalled: Buffer = clientHello.marshal()

        expect(marshalled).to.deep.equal(data)
    })
})
