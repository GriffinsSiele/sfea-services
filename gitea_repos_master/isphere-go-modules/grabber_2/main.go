package main

import (
	"bytes"
	"errors"
	"fmt"
	"git.i-sphere.ru/grabber2/controller"
	"io"
	"io/fs"
	"io/ioutil"
	"net"
	"os"
	"time"

	"github.com/pkg/sftp"
	"github.com/sirupsen/logrus"
	"golang.org/x/crypto/ssh"
)

const (
	PrivateKeyPath = "./var/cert/id_rsa"
)

func main() {
	if err := run(); err != nil {
		logrus.WithError(err).Error("cannot run application")
	}
}

func run() error {
	dummyHandler := &InMemoryHandler{}
	root := sftp.Handlers{
		FileGet:  dummyHandler,
		FileList: dummyHandler,
	}

	privateKeyBytes, err := os.ReadFile(PrivateKeyPath)
	if err != nil {
		return fmt.Errorf("failed to open private key: %w", err)
	}

	privateKey, err := ssh.ParsePrivateKey(privateKeyBytes)
	if err != nil {
		return fmt.Errorf("failed to parse private key:% w", err)
	}

	config := &ssh.ServerConfig{
		PasswordCallback: func(conn ssh.ConnMetadata, password []byte) (*ssh.Permissions, error) {
			if conn.User() == "test" && string(password) == "test" {
				return nil, nil
			}

			return nil, fmt.Errorf("password rejected for %q", conn.User())
		},
	}

	config.AddHostKey(privateKey)

	addr := os.Getenv("ADDR")
	if addr == "" {
		addr = ":8022"
	}

	listener, err := net.Listen("tcp", addr)
	if err != nil {
		return fmt.Errorf("failed to listen tcp: %w", err)
	}

	conn, err := listener.Accept()
	if err != nil {
		return fmt.Errorf("failed to accept tcp connections: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	defer conn.Close()

	_, newChannels, newRequests, err := ssh.NewServerConn(conn, config)
	if err != nil {
		return fmt.Errorf("failed to handshake: %w", err)
	}

	logrus.Infof("ssh server established: %s", addr)

	go ssh.DiscardRequests(newRequests)

	for newChannel := range newChannels {
		if newChannel.ChannelType() != "session" {
			if err = newChannel.Reject(ssh.UnknownChannelType, "unknown new channel type"); err != nil {
				return fmt.Errorf("failed to reject new channel: %w", err)
			}

			continue
		}

		channel, in, err := newChannel.Accept()
		if err != nil {
			return fmt.Errorf("failed to accept channel: %w", err)
		}

		go func(in <-chan *ssh.Request) {
			for req := range in {
				ok := false

				switch req.Type {
				case "subsystem":
					if string(req.Payload[4:]) == "sftp" {
						ok = true
					}
				}

				if err := req.Reply(ok, nil); err != nil {
					logrus.WithError(err).Error("cannot send response in request")
				}
			}
		}(in)

		server := sftp.NewRequestServer(channel, root)

		if err = server.Serve(); err != nil && !errors.Is(err, io.EOF) {
			return fmt.Errorf("sftp server completed with error: %w", err)
		}

		//goland:noinspection GoUnhandledErrorResult
		server.Close()
	}

	_ = newChannels
	_ = newRequests

	return nil
}

//func _main() {
//	defaultController := controller.NewDefaultController()
//
//	http.Handle("/api/v1/obrnadzor/7701537808-FBDRL.parquet", http.HandlerFunc(defaultController.Handle))
//
//	tls := true
//
//	if _, err := os.Stat(CertPath); err != nil && os.IsNotExist(err) {
//		logrus.WithError(err).Errorf("cert file is not readable")
//		tls = false
//	}
//
//	addr := os.Getenv("ADDR")
//	if addr == "" {
//		if tls {
//			addr = ":443"
//		} else {
//			addr = ":80"
//		}
//	}
//
//	logrus.Infof("server started on %s", addr)
//
//	var err error
//	if tls {
//		err = http.ListenAndServeTLS(addr, CertPath, KeyPath, nil)
//	} else {
//		err = http.ListenAndServe(addr, nil)
//	}
//
//	if err != nil {
//		logrus.WithError(err).Fatal("failed to run http server")
//	}
//}

type unbufferedReaderAt struct {
	R io.Reader
	N int64
}

func (u *unbufferedReaderAt) ReadAt(p []byte, off int64) (n int, err error) {
	if off < u.N {
		return 0, errors.New("invalid offset")
	}
	diff := off - u.N
	written, err := io.CopyN(ioutil.Discard, u.R, diff)
	u.N += written
	if err != nil {
		return 0, err
	}
	n, err = u.R.Read(p)
	u.N += int64(n)
	return
}

type WriteBuffer struct {
	d []byte
	m int
}

func NewWriteBuffer(size, max int) *WriteBuffer {
	if max < size && max >= 0 {
		max = size
	}
	return &WriteBuffer{make([]byte, size), max}
}

func (wb *WriteBuffer) SetMax(max int) {
	if max < len(wb.d) && max >= 0 {
		max = len(wb.d)
	}
	wb.m = max
}

func (wb *WriteBuffer) Bytes() []byte {
	return wb.d
}
func (wb *WriteBuffer) Shape() (int, int) {
	return len(wb.d), wb.m
}

func (wb *WriteBuffer) WriteAt(dat []byte, off int64) (int, error) {
	if int(off) < 0 {
		return 0, errors.New("Offset out of range (too small).")
	}

	if int(off)+len(dat) >= wb.m && wb.m > 0 {
		return 0, errors.New("Offset+data length out of range (too large).")
	}

	if int(off) == len(wb.d) {
		wb.d = append(wb.d, dat...)
		return len(dat), nil
	}

	if int(off)+len(dat) >= len(wb.d) {
		nd := make([]byte, int(off)+len(dat))
		copy(nd, wb.d)
		wb.d = nd
	}

	copy(wb.d[int(off):], dat)
	return len(dat), nil
}

type InMemoryHandler struct{}

func (t *InMemoryHandler) Fileread(req *sftp.Request) (io.ReaderAt, error) {
	if req.Filepath == "/obrnadzor__7701537808-FBDRL.parquet" {
		buf := bytes.NewBuffer([]byte{})
		ctrl := controller.NewDefaultController()
		ctrl.Handle(buf)
		return &unbufferedReaderAt{R: buf}, nil
	}

	return nil, fmt.Errorf("filepath does not exists")
}

func (t *InMemoryHandler) Filewrite(req *sftp.Request) (io.WriterAt, error) {
	return NewWriteBuffer(0, 10), nil
}

func (t *InMemoryHandler) Filecmd(req *sftp.Request) error {
	return nil
}

func (t *InMemoryHandler) Filelist(req *sftp.Request) (sftp.ListerAt, error) {
	return &listerat{
		&FakeFile{
			name: "obrnadzor__7701537808-FBDRL.parquet",
			mode: 0644,
		},
	}, nil
}

type listerat []os.FileInfo

func (t listerat) ListAt(ls []os.FileInfo, offset int64) (int, error) {
	var n int
	if offset >= int64(len(t)) {
		return 0, io.EOF
	}
	n = copy(ls, t[offset:])
	if n < len(ls) {
		return n, io.EOF
	}
	return n, nil
}

type FakeFile struct {
	name     string
	contents string
	mode     fs.FileMode
	offset   int
}

func (f *FakeFile) Reset() *FakeFile {
	f.offset = 0
	return f
}

func (f *FakeFile) Name() string {
	return f.name
}

func (f *FakeFile) Stat() (fs.FileInfo, error) {
	return f, nil
}

func (f *FakeFile) Read(p []byte) (int, error) {
	if f.offset >= len(f.contents) {
		return 0, io.EOF
	}
	n := copy(p, f.contents[f.offset:])
	f.offset += n
	return n, nil
}

func (f *FakeFile) Close() error {
	return nil
}

func (f *FakeFile) Size() int64 {
	return int64(len(f.contents))
}

func (f *FakeFile) Mode() fs.FileMode {
	return f.mode
}

func (f *FakeFile) ModTime() time.Time {
	return time.Time{}
}

func (f *FakeFile) IsDir() bool {
	return false
}

func (f *FakeFile) Sys() any {
	return nil
}

func (f *FakeFile) String() string {
	return fs.FormatFileInfo(f)
}
