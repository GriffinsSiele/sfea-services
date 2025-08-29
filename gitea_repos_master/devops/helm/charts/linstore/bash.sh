kubectl apply --server-side -k "https://github.com/piraeusdatastore/piraeus-operator//config/default?ref=v2"
# Verify the operator is running:
#$ kubectl wait pod --for=condition=Ready -n piraeus-datastore -l app.kubernetes.io/component=piraeus-operator pod/piraeus-operator-controller-manager-dd898f48c-bhbtv condition met