{{- define "app.getOrCreateSecret" -}}
{{- $root := index . 0 -}}
{{- $secretName := index . 1 -}}
{{- $key := index . 2 -}}
{{- $length := 32 -}}

{{- if ge (len .) 4 -}}
{{- $length = index . 3 -}}
{{- end -}}

{{- $existing := lookup "v1" "Secret" $root.Release.Namespace $secretName -}}
{{- if and $existing (hasKey $existing.data $key) -}}
{{- index $existing.data $key | b64dec -}}
{{- else -}}
{{- randAlphaNum $length -}}
{{- end -}}
{{- end -}}

{{- define "app.getOrCreateAppKey" -}}
{{- $root := index . 0 -}}
{{- $secretName := index . 1 -}}

{{- $existing := lookup "v1" "Secret" $root.Release.Namespace $secretName -}}
{{- if and $existing (hasKey $existing.data "APP_KEY") -}}
{{- index $existing.data "APP_KEY" | b64dec -}}
{{- else -}}
{{- printf "base64:%s" (randAlphaNum 32 | b64enc) -}}
{{- end -}}
{{- end -}}
