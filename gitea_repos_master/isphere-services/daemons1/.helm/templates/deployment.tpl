{{- $root := . }}
{{- $release := .Release }}
{{- $values := .Values }}
{{- $chart := .Chart }}
{{- range .Values.daemons }}
{{- $daemon := . }}
{{- include "deployment.yaml" (dict "Daemon" $daemon "Root" $root "Release" $release "Values" $values "Chart" $chart) }}
{{- end }}
