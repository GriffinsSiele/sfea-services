import net from 'net'
import { log } from 'npmlog'

import { Ja3Transform } from './ja3/Transformer'
import logger from './logger'

logger.level = 'silly'

const proxyServer: net.Server = net.createServer((clientSocket: net.Socket): void => {
    clientSocket.once('data', (data: Buffer): void => {
        const version: number = data[0]

        if (version === 0x05) { // Version 5
            const methodsCount: number = data[1]
            const methods: Buffer = data.slice(2, 2 + methodsCount)

            if (methods.includes(0x00)) { // Authentication method: No authentication
                clientSocket.write(Buffer.from([0x05, 0x00])) // Version 5, Method: No authentication required
                clientSocket.once('data', (data: Buffer): void => {
                    const version: number = data[0]
                    const command: number = data[1]
                    const addressType: number = data[3]

                    if (version === 0x05 && command === 0x01) { // Command: Connect
                        if (addressType === 0x01) { // IPv4
                            const address: Buffer = data.slice(4, 8)
                            const port: number = data.readUInt16BE(8)
                            const serverSocket: net.Socket = net.connect(port, address.join('.'), (): void => {
                                clientSocket.write(Buffer.from([
                                    0x05, // Version 5
                                    0x00, // Success
                                    0x00, // Reserved
                                    0x01, // IPv4
                                    0x00, 0x00, 0x00, 0x00, // IPv4 address
                                    0x00, 0x00, // Port
                                ]))

                                clientSocket.pipe(new Ja3Transform(null)).pipe(serverSocket)
                                serverSocket.pipe(new Ja3Transform(null)).pipe(clientSocket)
                            })

                            clientSocket.on('error', (error: Error): void => {
                                logger.error('', 'Client socket error:', error)
                                serverSocket.end()
                            })

                            serverSocket.on('error', (error: Error): void => {
                                logger.error('', 'Server socket error:', error)
                                clientSocket.end()
                            })
                        } else {
                            logger.error('', 'Unsupported address type')
                            clientSocket.end()
                        }
                    }
                })
            } else {
                logger.error('', 'No supported authentication method')
                clientSocket.end()
            }
        } else {
            logger.error('', 'Invalid version')
            clientSocket.end()
        }
    })
})

const port: number = 1080
proxyServer.listen(port, (): void => {
    logger.info('', 'Proxy server started')
})
