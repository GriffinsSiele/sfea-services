{{ define "cronjob" }}
apiVersion: batch/v1
kind: CronJob
metadata:
  name: {{ include "chart.fullname" .Root }}-cronjob-{{ .Name }}
  labels:
    {{- include "chart.labels" .Root | nindent 4 }}
    app.kubernetes.io/component: cron
    app.kubernetes.io/name: {{ include "chart.fullname" .Root }}-{{ .Name }}
  annotations:
    helm.sh/hook: post-install,post-upgrade
spec:
  schedule: {{ .Schedule | quote }}
  jobTemplate:
    spec:
      template:
        spec:
          restartPolicy: OnFailure
          containers:
            - name: cronjob
              image: "{{ .Root.Values.app.image.repository }}:{{ .Root.Values.app.image.tag }}"
              imagePullPolicy: {{ .Root.Values.app.image.pullPolicy }}
              command: [ "bin/console" ]
              args:
                - {{ .Command | quote }}
                - "--database"
                - "postgres://grabber:89yATBJbsdzrPmLu@172.16.99.1:5432/grabber_prod"
                - "--no-cache"
              envFrom:
                - configMapRef:
                    name: {{ include "chart.name" .Root }}-configmap
          serviceAccountName: {{ include "chart.serviceAccountName" .Root }}
          imagePullSecrets:
            - name: {{ include "chart.name" .Root }}-registry
          securityContext:
            {{- toYaml .Root.Values.podSecurityContext | nindent 12 }}
          {{- with .Root.Values.serviceAccount.affinity }}
          affinity:
            {{- toYaml . | nindent 12 }}
          {{- end }}
          {{- with .Root.Values.nodeSelector }}
          nodeSelector:
            {{- toYaml . | nindent 12 }}
          {{- end }}
          {{- with .Root.Values.tolerations }}
          tolerations:
            {{- toYaml . | nindent 12 }}
          {{- end }}
---
{{ end }}
