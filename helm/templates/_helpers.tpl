{{/*
Expand the name of the chart.
*/}}
{{- define "app.name" -}}
{{- default .Chart.Name .Values.nameOverride | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Create a default fully qualified app name.
*/}}
{{- define "app.fullname" -}}
{{- if .Values.fullnameOverride }}
{{- .Values.fullnameOverride | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- $name := default .Chart.Name .Values.nameOverride }}
{{- if contains $name .Release.Name }}
{{- .Release.Name | trunc 63 | trimSuffix "-" }}
{{- else }}
{{- printf "%s-%s" .Release.Name $name | trunc 63 | trimSuffix "-" }}
{{- end }}
{{- end }}
{{- end }}

{{/*
Create chart name and version as used by the chart label.
*/}}
{{- define "app.chart" -}}
{{- printf "%s-%s" .Chart.Name .Chart.Version | replace "+" "_" | trunc 63 | trimSuffix "-" }}
{{- end }}

{{/*
Common labels
*/}}
{{- define "app.labels" -}}
helm.sh/chart: {{ include "app.chart" . }}
{{ include "app.selectorLabels" . }}
{{- if .Chart.AppVersion }}
app.kubernetes.io/version: {{ .Chart.AppVersion | quote }}
{{- end }}
app.kubernetes.io/managed-by: {{ .Release.Service }}
{{- end }}

{{/*
Selector labels
*/}}
{{- define "app.selectorLabels" -}}
app.kubernetes.io/name: {{ include "app.name" . }}
app.kubernetes.io/instance: {{ .Release.Name }}
{{- end }}

{{/*
Create the name of the service account to use
*/}}
{{- define "app.serviceAccountName" -}}
{{- if .Values.serviceAccount.create }}
{{- default (include "app.fullname" .) .Values.serviceAccount.name }}
{{- else }}
{{- default "default" .Values.serviceAccount.name }}
{{- end }}
{{- end }}

{{/*
MySQL service name
*/}}
{{- define "app.mysql.fullname" -}}
{{- printf "%s-mysql" (include "app.fullname" .) }}
{{- end }}

{{/*
Redis service name
*/}}
{{- define "app.redis.fullname" -}}
{{- printf "%s-redis" (include "app.fullname" .) }}
{{- end }}

{{/*
MySQL host
*/}}
{{- define "app.mysql.host" -}}
{{- printf "%s.%s.svc.cluster.local" (include "app.mysql.fullname" .) .Release.Namespace }}
{{- end }}

{{/*
Redis host
*/}}
{{- define "app.redis.host" -}}
{{- printf "%s.%s.svc.cluster.local" (include "app.redis.fullname" .) .Release.Namespace }}
{{- end }}

{{/*
Init containers that wait for the web service to be ready
*/}}
{{- define "app.initContainers.waitForWebService" -}}
- name: wait-for-web
  image: busybox:1.36
  command:
    - sh
    - -c
    - |
      echo "Waiting for web service to be ready..."
      until nc -z {{ include "app.fullname" . }}-web 8080; do
        echo "Web service not ready yet. Waiting..."
        sleep 2
      done
      echo "Web service is ready!"
{{- end }}

{{/*
Init containers that wait for MySQL and Redis to be ready
*/}}
{{- define "app.initContainers.waitForDependencies" -}}
{{- if .Values.mysql.enabled }}
- name: wait-for-mysql
  image: busybox:1.36
  command:
    - sh
    - -c
    - |
      echo "Waiting for MySQL to be ready..."
      until nc -z {{ include "app.mysql.fullname" . }} {{ .Values.mysql.service.port }}; do
        echo "MySQL not ready yet. Waiting..."
        sleep 2
      done
      echo "MySQL is ready!"
{{- end }}
{{- if .Values.redis.enabled }}
- name: wait-for-redis
  image: busybox:1.36
  command:
    - sh
    - -c
    - |
      echo "Waiting for Redis to be ready..."
      until nc -z {{ include "app.redis.fullname" . }} {{ .Values.redis.service.port }}; do
        echo "Redis not ready yet. Waiting..."
        sleep 2
      done
      echo "Redis is ready!"
{{- end }}
{{- end }}
