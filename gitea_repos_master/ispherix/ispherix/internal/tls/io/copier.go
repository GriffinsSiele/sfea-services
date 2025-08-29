package io

import (
	"encoding/binary"
	"errors"
	"fmt"
	"io"
	"net"

	"go.i-sphere.ru/ispherix/pkg/tls"
	"go.i-sphere.ru/ispherix/pkg/tls/types"
)

func Copy(dst, src net.Conn, fn func(*tls.Frame) error) (int64, error) {
	var written int64
	var err error
	buf := make([]byte, 1)
	buffer := make([]byte, 0)

	for {
		readBytes, errRead := src.Read(buf)
		if readBytes > 0 {
			buffer = append(buffer, buf[0:readBytes]...)

		l:
			if len(buffer) > 4 {
				contentType := buffer[0]
				protocolVersion := binary.BigEndian.Uint16(buffer[1:3])
				contentLength := int(binary.BigEndian.Uint16(buffer[3:5]))

				if len(buffer) >= 5+contentLength {
					content := buffer[5 : 5+contentLength]
					frame := &tls.Frame{
						Source:          src,
						Destination:     dst,
						Type:            types.FrameType(contentType),
						ProtocolVersion: types.ProtocolVersion(protocolVersion),
					}

					if errParse := frame.Parse(content); errParse != nil {
						return written, fmt.Errorf("failed to parse frame content: %w", errParse)
					}

					if errFn := fn(frame); errFn != nil {
						return written, fmt.Errorf("frame processing failed: %w", errFn)
					}

					buffer = buffer[5+contentLength:]
					if len(buffer) > 0 {
						goto l
					}
				}
			}

			writeBytes, errWrite := dst.Write(buf[0:readBytes])
			if writeBytes < 0 || readBytes != writeBytes {
				writeBytes = 0
				if errWrite == nil {
					errWrite = errors.New("invalid write result")
				}
			}

			written += int64(writeBytes)
			if errWrite != nil {
				err = errWrite
			}
		}

		if errRead != nil {
			if !errors.Is(errRead, io.EOF) {
				err = errRead
			}
			break
		}
	}

	return written, err
}

func CloseRead(conn net.Conn) {
	if tcpConn, ok := conn.(*net.TCPConn); ok {
		//goland:noinspection GoUnhandledErrorResult
		tcpConn.CloseRead()
	}
}

func CloseWrite(conn net.Conn) {
	if tcpConn, ok := conn.(*net.TCPConn); ok {
		//goland:noinspection GoUnhandledErrorResult
		tcpConn.CloseWrite()
	}
}
