package types

import (
	"fmt"
	"strconv"
)

type ExtensionType uint16

const (
	ExtensionTypeServerName                          ExtensionType = 0
	ExtensionTypeMaxFragmentLength                   ExtensionType = 1
	ExtensionTypeClientCertificateUrl                ExtensionType = 2
	ExtensionTypeTrustedCaKeys                       ExtensionType = 3
	ExtensionTypeTruncatedHmac                       ExtensionType = 4
	ExtensionTypeStatusRequest                       ExtensionType = 5
	ExtensionTypeUserMapping                         ExtensionType = 6
	ExtensionTypeClientAuthZ                         ExtensionType = 7
	ExtensionTypeServerAuthZ                         ExtensionType = 8
	ExtensionTypeCertType                            ExtensionType = 9
	ExtensionTypeSupportedGroups                     ExtensionType = 10 // ellipticCurves
	ExtensionTypeEcPointFormats                      ExtensionType = 11
	ExtensionTypeSRP                                 ExtensionType = 12
	ExtensionTypeSignatureAlgorithms                 ExtensionType = 13
	ExtensionTypeUseSRTP                             ExtensionType = 14
	ExtensionTypeHeartbeat                           ExtensionType = 15
	ExtensionTypeApplicationLayerProtocolNegotiation ExtensionType = 16
	ExtensionTypeStatusRequestV2                     ExtensionType = 17
	ExtensionTypeSignedCertificateTimestamp          ExtensionType = 18
	ExtensionTypeClientCertificateType               ExtensionType = 19
	ExtensionTypeServerCertificateType               ExtensionType = 20
	ExtensionTypePadding                             ExtensionType = 21
	ExtensionTypeEncryptThenMAC                      ExtensionType = 22
	ExtensionTypeExtendedMasterSecret                ExtensionType = 23
	ExtensionTypeTokenBinding                        ExtensionType = 24
	ExtensionTypeCachedInfo                          ExtensionType = 25
	ExtensionTypeTLSLTS                              ExtensionType = 26
	ExtensionTypeCompressCertificate                 ExtensionType = 27
	ExtensionTypeRecordSizeLimit                     ExtensionType = 28
	ExtensionTypePwdProtect                          ExtensionType = 29
	ExtensionTypePwdClear                            ExtensionType = 30
	ExtensionTypePasswordSalt                        ExtensionType = 31
	ExtensionTypeTicketPinning                       ExtensionType = 32
	ExtensionTypeTlsCertWithExternPsk                ExtensionType = 33
	ExtensionTypeDelegatedCredential                 ExtensionType = 34
	ExtensionTypeSessionTicket                       ExtensionType = 35 // sessionticket tls
	ExtensionTypeTLMSP                               ExtensionType = 36
	ExtensionTypeTLMSPProxying                       ExtensionType = 37
	ExtensionTypeTLMSPDelegate                       ExtensionType = 38
	ExtensionTypeSupportedEKTCiphers                 ExtensionType = 39
	ExtensionTypePreSharedKey                        ExtensionType = 41
	ExtensionTypeEarlyData                           ExtensionType = 42
	ExtensionTypeSupportedVersions                   ExtensionType = 43
	ExtensionTypeCookie                              ExtensionType = 44
	ExtensionTypePSKKeyExchangeModes                 ExtensionType = 45
	ExtensionTypeCertificateAuthorities              ExtensionType = 47
	ExtensionTypeOIDFilters                          ExtensionType = 48
	ExtensionTypePostHandshakeAuth                   ExtensionType = 49
	ExtensionTypeSignatureAlgorithmsCert             ExtensionType = 50
	ExtensionTypeKeyShare                            ExtensionType = 51
	ExtensionTypeTransparencyInfo                    ExtensionType = 52
	ExtensionTypeConnectionIDDeprecated              ExtensionType = 53
	ExtensionTypeConnectionID                        ExtensionType = 54
	ExtensionTypeExternalIDHash                      ExtensionType = 55
	ExtensionTypeExternalSessionID                   ExtensionType = 56
	ExtensionTypeQUICTransportParameters             ExtensionType = 57
	ExtensionTypeTicketRequest                       ExtensionType = 58
	ExtensionTypeDNSSECChain                         ExtensionType = 59
	ExtensionTypeSequenceNumberEncryptionAlgorithms  ExtensionType = 60
	ExtensionTypeRRC                                 ExtensionType = 61
	ExtensionTypeEchOuterExtensions                  ExtensionType = 64768
	ExtensionTypeEncryptedClientHello                ExtensionType = 65037
	ExtensionTypeRenegotiationInfo                   ExtensionType = 65281
)

// String https://www.iana.org/assignments/tls-extensiontype-values/tls-extensiontype-values.xhtml
func (t *ExtensionType) String() string {
	switch *t {
	case ExtensionTypeServerName:
		return "server_name"
	case ExtensionTypeMaxFragmentLength:
		return "max_fragment_length"
	case ExtensionTypeClientCertificateUrl:
		return "client_certificate_url"
	case ExtensionTypeTrustedCaKeys:
		return "trusted_ca_keys"
	case ExtensionTypeTruncatedHmac:
		return "truncated_hmac"
	case ExtensionTypeStatusRequest:
		return "status_request"
	case ExtensionTypeUserMapping:
		return "user_mapping"
	case ExtensionTypeClientAuthZ:
		return "client_authz"
	case ExtensionTypeServerAuthZ:
		return "server_authz"
	case ExtensionTypeCertType:
		return "cert_type"
	case ExtensionTypeSupportedGroups:
		return "supported_groups"
	case ExtensionTypeEcPointFormats:
		return "ec_point_formats"
	case ExtensionTypeSRP:
		return "srp"
	case ExtensionTypeSignatureAlgorithms:
		return "signature_algorithms"
	case ExtensionTypeUseSRTP:
		return "use_srtp"
	case ExtensionTypeHeartbeat:
		return "heartbeat"
	case ExtensionTypeApplicationLayerProtocolNegotiation:
		return "application_layer_protocol_negotiation"
	case ExtensionTypeStatusRequestV2:
		return "status_request_v2"
	case ExtensionTypeSignedCertificateTimestamp:
		return "signed_certificate_timestamp"
	case ExtensionTypeClientCertificateType:
		return "client_certificate_type"
	case ExtensionTypeServerCertificateType:
		return "server_certificate_type"
	case ExtensionTypePadding:
		return "padding"
	case ExtensionTypeEncryptThenMAC:
		return "encrypt_then_mac"
	case ExtensionTypeExtendedMasterSecret:
		return "extended_master_secret"
	case ExtensionTypeTokenBinding:
		return "token_binding"
	case ExtensionTypeCachedInfo:
		return "cached_info"
	case ExtensionTypeTLSLTS:
		return "tls_lts"
	case ExtensionTypeCompressCertificate:
		return "compress_certificate"
	case ExtensionTypeRecordSizeLimit:
		return "record_size_limit"
	case ExtensionTypePwdProtect:
		return "pwd_protect"
	case ExtensionTypePwdClear:
		return "pwd_clear"
	case ExtensionTypePasswordSalt:
		return "password_salt"
	case ExtensionTypeTicketPinning:
		return "ticket_pinning"
	case ExtensionTypeTlsCertWithExternPsk:
		return "tls_cert_with_extern_psk"
	case ExtensionTypeDelegatedCredential:
		return "delegated_credential"
	case ExtensionTypeSessionTicket:
		return "session_ticket"
	case ExtensionTypeTLMSP:
		return "tlmsp"
	case ExtensionTypeTLMSPProxying:
		return "tlmsp_proxying"
	case ExtensionTypeTLMSPDelegate:
		return "tlmsp_delegate"
	case ExtensionTypeSupportedEKTCiphers:
		return "supported_ekt_ciphers"
	case ExtensionTypePreSharedKey:
		return "pre_shared_key"
	case ExtensionTypeEarlyData:
		return "early_data"
	case ExtensionTypeSupportedVersions:
		return "supported_versions"
	case ExtensionTypeCookie:
		return "cookie"
	case ExtensionTypePSKKeyExchangeModes:
		return "psk_key_exchange_modes"
	case ExtensionTypeCertificateAuthorities:
		return "certificate_authorities"
	case ExtensionTypeOIDFilters:
		return "oid_filters"
	case ExtensionTypePostHandshakeAuth:
		return "post_handshake_auth"
	case ExtensionTypeSignatureAlgorithmsCert:
		return "signature_algorithms_cert"
	case ExtensionTypeKeyShare:
		return "key_share"
	case ExtensionTypeTransparencyInfo:
		return "transparency_info"
	case ExtensionTypeConnectionIDDeprecated:
		return "connection_id_deprecated"
	case ExtensionTypeConnectionID:
		return "connection_id"
	case ExtensionTypeExternalIDHash:
		return "external_id_hash"
	case ExtensionTypeExternalSessionID:
		return "external_session_id"
	case ExtensionTypeQUICTransportParameters:
		return "quic_transport_parameters"
	case ExtensionTypeTicketRequest:
		return "ticket_request"
	case ExtensionTypeDNSSECChain:
		return "dnssec_chain"
	case ExtensionTypeSequenceNumberEncryptionAlgorithms:
		return "sequence_number_encryption_algorithms"
	case ExtensionTypeRRC:
		return "rrc"
	case ExtensionTypeEchOuterExtensions:
		return "ech_outer_extensions"
	case ExtensionTypeEncryptedClientHello:
		return "encrypted_client_hello"
	case ExtensionTypeRenegotiationInfo:
		return "renegotiation_info"
	default:
		return fmt.Sprintf("0x%04x", *t)
	}
}

func (t *ExtensionType) MarshalJSON() ([]byte, error) {
	return []byte(strconv.Quote(t.String())), nil
}
