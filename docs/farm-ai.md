# Farm sustainability assistant

Sofiene's AI feature gives practical advice based on a farm's recorded region,
area, olive variety, farming method and irrigation method. It also flags missing
information and explains the limits of its advice. Advice is temporary: it never
updates the farm, approves a certificate or claims measured environmental savings.

## Configure Gemini

Get a Gemini API key from Google AI Studio and save it only in your local `.env`:

```dotenv
FARM_AI_PROVIDER=gemini
FARM_AI_MODEL=gemini-3.1-flash-lite
GEMINI_API_KEY=your_private_api_key
```

Then run `php artisan config:clear`. Log in as a producer, open **Farms**, open
a farm and click **Generate suggestions** in the **Sustainability assistant**.
An admin can use the same assistant from the farm inspection page.

Gemini 3.1 Flash-Lite was live-tested on 8 October 2026. Model access and quotas
depend on your Google project. If a model is retired, replace `FARM_AI_MODEL`
with an available Gemini text model supporting structured JSON output.
Never place an API key in `VITE_*`, Blade, a URL, `.env.example` or Git.
If a key was shared publicly or pasted in chat, replace it in Google AI Studio.

## What to explain to the teacher

1. The producer requests advice for a farm they own; roles and policies also allow admin inspection.
2. Laravel sends only selected farm facts through its HTTP client to Gemini.
3. A system instruction asks for advice, missing-data flags and limitations.
4. Gemini returns structured JSON. Laravel validates the structure and text lengths.
5. Blade escapes and displays the advice alongside the existing farm and harvest information.
6. No farm data is modified. A producer must review the suggestions with local agronomic guidance.

Contacts, producer names, emails, precise GPS, private farm notes and logo files
are excluded. Notes and coordinates are represented only by presence/absence.
The endpoint is limited to five requests per minute. Missing credentials,
quota exhaustion, blocked output and network failures produce a readable message
while normal farm management remains available. No simulated advice is used as
a fallback.

## Other supported providers

For OpenAI, set provider `openai`, model `gpt-4.1-mini` and `OPENAI_API_KEY`.
For Ollama, set provider `ollama`, the installed model name and
`OLLAMA_URL=http://127.0.0.1:11434`. These alternatives use the same validated
response and privacy rules. Farm AI configuration is separate from the lab AI.

## Laboratory configuration

The lab explanation assistant also uses Gemini and shares the private `GEMINI_API_KEY`:

```dotenv
LAB_AI_MODEL=gemini-3.1-flash-lite
GEMINI_API_KEY=your_private_gemini_key
```

Keep exactly one `FARM_AI_PROVIDER` and one `FARM_AI_MODEL` line in `.env`.
Leave them set to `gemini` and your Gemini model when configuring the lab.
`LAB_AI_MODEL` selects only the lab model; `FARM_AI_MODEL` selects only the farm model.
Run `php artisan config:clear` after changing settings. OpenAI credentials are
only needed if you explicitly select the farm's optional OpenAI provider.

## Check the installation

```powershell
php artisan test --filter FarmAssistantTest
php scripts/test-mysql.php
```

Automated AI tests use mocked providers and never spend an API quota. A real
demonstration requires the private key and an available provider.

On Windows, `cURL error 60` means PHP lacks a trusted CA certificate bundle.
Configure `curl.cainfo` and `openssl.cafile` in the PHP installation's `php.ini`
to point to a current trusted PEM bundle, then restart the PHP server. Keep TLS
verification enabled.

References: [Google AI Studio](https://aistudio.google.com/apikey),
[Gemini API](https://ai.google.dev/api/generate-content),
[Gemini structured output](https://ai.google.dev/gemini-api/docs/structured-output).
