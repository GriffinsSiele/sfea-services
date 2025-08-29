install-provisioner:
	@helm upgrade -i -n csi-s3 --create-namespace csi-s3-provisioner -f values.yaml yandex-s3/csi-s3

uninstall-provisioner:
	@helm uninstall -n csi-s3 csi-s3-provisioner

