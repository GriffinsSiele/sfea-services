package socks5

import (
	"bufio"
	"bytes"
	"context"
	"encoding/base64"
	"errors"
	"fmt"
	"io"
	"net"
	"net/http"
	"slices"
	"strconv"
	"time"

	main_service "go.i-sphere.ru/proxphere-simple/internal/main-service"
	"go.i-sphere.ru/proxphere-simple/internal/rules"
)

type Handler struct {
	optionRepository *main_service.Repository
	ruleRepository   *rules.Repository

	defaultConnectionTimeout time.Duration
}

func NewHandler(optionRepository *main_service.Repository, ruleRepository *rules.Repository) *Handler {
	return &Handler{
		optionRepository: optionRepository,
		ruleRepository:   ruleRepository,

		defaultConnectionTimeout: 5 * time.Second,
	}
}

func (h *Handler) Handle(ctx context.Context, conn net.Conn) error {
	//goland:noinspection GoUnhandledErrorResult
	defer conn.Close()

	clientHello := &ClientHello{}
	if err := clientHello.Read(ctx, conn); err != nil {
		return fmt.Errorf("failed to read client hello: %w", err)
	}

	rule, err := h.handleClientHello(ctx, conn, clientHello)
	if err != nil {
		return fmt.Errorf("failed to handle client hello: %w", err)
	}

	clientReq, err := h.readClientRequest(ctx, conn)
	if err != nil {
		return fmt.Errorf("failed to read client request: %w", err)
	}

	if err = h.validateClientRequest(ctx, conn, clientReq); err != nil {
		return fmt.Errorf("failed to validate client request: %w", err)
	}

	destConn, err := h.establishDestinationConnection(ctx, conn, clientReq, rule)
	if err != nil {
		return fmt.Errorf("failed to establish destination connection: %w", err)
	}
	//goland:noinspection GoUnhandledErrorResult
	defer destConn.Close()

	if err = h.grantAccessAndTransferData(ctx, conn, destConn); err != nil {
		return fmt.Errorf("failed to grant access and transfer data: %w", err)
	}

	return nil
}

func (h *Handler) handleClientHello(ctx context.Context, conn net.Conn, clientHello *ClientHello) (*rules.Rule, error) {
	switch {
	case slices.Contains(clientHello.AcceptMethods, MethodUsernamePassword):
		rule, err := h.handleClientAuthMethodUsernamePassword(ctx, conn)
		if err != nil {
			return nil, fmt.Errorf("failed to handle client auth method username/password: %w", err)
		}

		return rule, nil

	case slices.Contains(clientHello.AcceptMethods, MethodNoAuthentication):
		serverHello := ServerHello{Method: MethodNoAuthentication}
		if err := serverHello.Write(ctx, conn); err != nil {
			return nil, fmt.Errorf("failed to write server hello with no authentication method: %w", err)
		}

		return nil, nil

	default:
		serverHello := ServerHello{Method: MethodUnsupported}
		_ = serverHello.Write(ctx, conn)

		return nil, fmt.Errorf("unsupported authentication method: %v", clientHello.AcceptMethods)
	}
}

func (h *Handler) handleClientAuthMethodUsernamePassword(ctx context.Context, conn net.Conn) (*rules.Rule, error) {
	serverHello := ServerHello{Method: MethodUsernamePassword}
	if err := serverHello.Write(ctx, conn); err != nil {
		return nil, fmt.Errorf("failed to write server hello with username/password method: %w", err)
	}

	var clientAuthReq ClientAuthenticationRequest
	if err := clientAuthReq.Read(ctx, conn); err != nil {
		return nil, fmt.Errorf("failed to read client authentication request: %w", err)
	}

	rule, err := h.ruleRepository.FindOneByUsernameAndPassword(ctx, string(clientAuthReq.Username), string(clientAuthReq.Password))
	if err != nil {
		serverAuthResp := &ServerAuthenticationResponse{Status: AuthenticationStatusFailed}
		_ = serverAuthResp.Write(ctx, conn)

		return nil, fmt.Errorf("failed to find rule by username: %s: %w", string(clientAuthReq.Username), err)
	}

	serverAuthResp := ServerAuthenticationResponse{Status: AuthenticationStatusSuccess}
	if err = serverAuthResp.Write(ctx, conn); err != nil {
		return nil, fmt.Errorf("failed to write server authentication response with success status: %w", err)
	}

	return rule, nil
}

func (h *Handler) readClientRequest(ctx context.Context, conn net.Conn) (*ClientRequest, error) {
	clientReq := &ClientRequest{}
	if err := clientReq.Read(ctx, conn); err != nil {
		if errors.Is(err, ErrInvalidAddressType) {
			serverResp := &ServerResponse{Status: ResponseStatusAddressTypeNotSupported}
			_ = serverResp.Write(ctx, conn)

			return nil, fmt.Errorf("unsupported address type: %x", clientReq.DestinationAddress.Type)
		}

		return nil, fmt.Errorf("failed to read client request: %w", err)
	}

	return clientReq, nil
}

func (h *Handler) validateClientRequest(ctx context.Context, conn net.Conn, clientReq *ClientRequest) error {
	if clientReq.Command != CommandConnect {
		serverResp := ServerResponse{Status: ResponseStatusCommandNotSupported}
		_ = serverResp.Write(ctx, conn)

		return fmt.Errorf("unsupported command: %x", clientReq.Command)
	}

	return nil
}

func (h *Handler) establishDestinationConnection(ctx context.Context, conn net.Conn, clientRequest *ClientRequest, rule *rules.Rule) (net.Conn, error) {
	if rule == nil || !rule.Proxy.Enabled {
		directConn, err := h.establishDirectConnection(ctx, conn, clientRequest)
		if err != nil {
			serverResp := &ServerResponse{Status: ResponseStatusNetworkUnreachable}
			_ = serverResp.Write(ctx, conn)

			return nil, fmt.Errorf("failed to establish direct connection: %w", err)
		}

		return directConn, nil
	}

	var proxies []*main_service.Option

	if len(rule.Proxy.Groups) > 0 {
		if proxies_, err := h.optionRepository.FindByGroups(ctx, rule.Proxy.Groups...); err != nil {
			serverResp := &ServerResponse{Status: ResponseStatusNetworkUnreachable}
			_ = serverResp.Write(ctx, conn)

			return nil, fmt.Errorf("failed to find proxies by groups: %w", err)
		} else {
			proxies = proxies_
		}
	} else {
		serverResp := &ServerResponse{Status: ResponseStatusNetworkUnreachable}
		_ = serverResp.Write(ctx, conn)

		return nil, errors.New("misconfigured proxy filter options")
	}

	if len(proxies) == 0 {
		serverResp := &ServerResponse{Status: ResponseStatusNetworkUnreachable}
		_ = serverResp.Write(ctx, conn)

		return nil, errors.New("no proxies found by group")
	}

	proxyConn, err := h.establishProxyConnection(ctx, conn, clientRequest, proxies)
	if err != nil {
		serverResp := &ServerResponse{Status: ResponseStatusNetworkUnreachable}
		_ = serverResp.Write(ctx, conn)

		return nil, fmt.Errorf("failed to establish proxy connection: %w", err)
	}

	return proxyConn, nil
}

func (h *Handler) establishDirectConnection(ctx context.Context, conn net.Conn, clientRequest *ClientRequest) (net.Conn, error) {
	addr := net.JoinHostPort(
		string(clientRequest.DestinationAddress.Addr),
		strconv.FormatUint(uint64(clientRequest.DestinationPort), 10),
	)

	dialer := net.Dialer{Timeout: h.defaultConnectionTimeout}
	directConn, err := dialer.DialContext(ctx, "tcp", addr)
	if err != nil {
		serverResponse := ServerResponse{Status: ResponseStatusHostUnreachable}
		_ = serverResponse.Write(ctx, conn)

		return nil, fmt.Errorf("failed to connect to destination: %w", err)
	}

	return directConn, nil
}

func (h *Handler) establishProxyConnection(ctx context.Context, conn net.Conn, clientRequest *ClientRequest, proxies []*main_service.Option) (net.Conn, error) {
	cancelCtx, cancel := context.WithCancelCause(ctx)
	defer cancel(nil)

	proxyConnCh := make(chan net.Conn)

	for _, proxy := range proxies {
		go h.connectToProxy(cancelCtx, proxyConnCh, proxy, clientRequest)
	}

	select {
	case proxyConn := <-proxyConnCh:
		cancel(&cancelProxiesExcept{proxyConn: proxyConn})
		return proxyConn, nil

	case <-time.After(1 * time.Second):
		serverResp := &ServerResponse{Status: ResponseStatusTTLExpired}
		_ = serverResp.Write(cancelCtx, conn)

		return nil, errors.New("TTL expired for all proxies")
	}
}

func (h *Handler) connectToProxy(ctx context.Context, proxyConnCh chan net.Conn, proxy *main_service.Option, clientRequest *ClientRequest) {
	addr := net.JoinHostPort(proxy.Server, strconv.Itoa(proxy.Port))
	dialer := net.Dialer{Timeout: h.defaultConnectionTimeout}
	proxyConn, err := dialer.DialContext(ctx, "tcp", addr)
	if err != nil {
		return
	}

	go h.closeProxyConnIfNotSelected(ctx, proxyConn)

	proxyReqBytes := h.buildProxyRequestBytes(proxy, clientRequest)
	if _, err = proxyConn.Write(bytes.Join(proxyReqBytes, []byte("\r\n"))); err != nil {
		return
	}

	if proxyResp, err := http.ReadResponse(bufio.NewReader(proxyConn), nil); err != nil || proxyResp.StatusCode != http.StatusOK {
		return
	}

	proxyConnCh <- proxyConn
}

func (h *Handler) closeProxyConnIfNotSelected(ctx context.Context, proxyConn net.Conn) {
	select {
	case <-ctx.Done():
		var cause *cancelProxiesExcept
		switch {
		case errors.As(context.Cause(ctx), &cause):
			if cause.proxyConn != proxyConn {
				//goland:noinspection GoUnhandledErrorResult
				proxyConn.Close()
			}
		}
	}
}

func (h *Handler) buildProxyRequestBytes(proxy *main_service.Option, clientRequest *ClientRequest) [][]byte {
	addr := net.JoinHostPort(string(clientRequest.DestinationAddress.Addr), strconv.Itoa(int(clientRequest.DestinationPort)))
	reqBytes := [][]byte{
		[]byte(fmt.Sprintf("CONNECT %s HTTP/1.0", addr)),
	}

	if proxy.Login != "" || proxy.Password != "" {
		reqBytes = append(reqBytes, []byte(fmt.Sprintf(
			"Proxy-Authorization: Basic %s",
			base64.StdEncoding.EncodeToString([]byte(fmt.Sprintf(
				"%s:%s",
				proxy.Login,
				proxy.Password,
			))),
		)))
	}

	return append(reqBytes, [][]byte{nil, nil}...)
}

func (h *Handler) grantAccessAndTransferData(ctx context.Context, conn, destConn net.Conn) error {
	serverResp := ServerResponse{Status: ResponseStatusGranted}
	if err := serverResp.Write(ctx, conn); err != nil {
		return fmt.Errorf("failed to write server response with granted status: %w", err)
	}

	//goland:noinspection GoUnhandledErrorResult
	go io.Copy(conn, destConn)

	if _, err := io.Copy(destConn, conn); err != nil {
		return fmt.Errorf("failed to transfer traffic: %w", err)
	}

	return nil
}

type cancelProxiesExcept struct {
	error
	proxyConn net.Conn
}
