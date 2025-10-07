Gemini-TTS is the latest evolution of our Text-to-Speech technology that's moving beyond just naturalness to giving granular control over generated audio using text-based prompts. Using Gemini-TTS, you can synthesize single or multi-speaker speech from short snippets to long-form narratives, precisely dictating style, accent, pace, tone, and even emotional expression, all steerable through natural-language prompts.

To explore this model in the console, see the Gemini-TTS model card in the Model Garden (accessible using the Media Studio tab).

Try Gemini-TTS on Vertex AI (Vertex AI Studio)

Gemini-TTS capabilities are supported by the following:

gemini-2.5-flash-tts: Gemini 2.5 Flash TTS is good for cost-efficient everyday TTS applications.

gemini-2.5-pro-tts: Gemini 2.5 Pro TTS is good for controllable speech generation (TTS) and for state-of-the-art quality of complex prompts.

Model	Optimized for	Input modality	Output modality	Single speaker	Multi-speaker
Gemini 2.5 Flash TTS	Low latency, controllable, single- and multi-speaker Text-to-Speech audio generation for cost-efficient everyday applications	Text	Audio	✔️	✔️
Gemini 2.5 Pro TTS	High control for structured workflows like podcast generation, audiobooks, customer support, and more	Text	Audio	✔️	✔️
Additional controls and capabilities include the following:

Natural conversation: Voice interactions of remarkable quality, more appropriate expressivity, and prosody (patterns of rhythm) are delivered with very low latency so you can converse fluidly.

Style control: Using natural language prompts, you can adapt the delivery within the conversation by steering it to adopt specific accents and produce a range of tones and expressions including a whisper.

Dynamic performance: These models can bring text to life for expressive readings of poetry, newscasts, and engaging storytelling. They can also perform with specific emotions and produce accents when requested.

Enhanced pace and pronunciation control: Controlling delivery speed helps to ensure more accuracy in pronunciation including specific words.

Examples

model: "gemini-2.5-pro-tts"
prompt: "You are having a casual conversation with a friend. Say the following in a friendly and amused way."
text: "hahah I did NOT expect that. Can you believe it!."
speaker: "Callirhoe"


model: "gemini-2.5-flash-tts"
prompt: "Say the following in a curious way"
text: "OK, so... tell me about this [uhm] AI thing.",
speaker: "Orus"


model: "gemini-2.5-flash-tts"
prompt: "Say the following"
text: "[extremely fast] Availability and terms may vary. Check our website or your local store for complete details and restrictions."
speaker: "Kore"

For information on how to use these voices programmatically, see Use Gemini-TTS section.

Voice Options
Gemini-TTS offers a wide range of voice options similar to our existing Chirp 3: HD Voices, each with distinct characteristics:

Name	Gender	Demo
Achernar	Female	
Achird	Male	
Algenib	Male	
Algieba	Male	
Alnilam	Male	
Aoede	Female	
Autonoe	Female	
Callirrhoe	Female	
Charon	Male	
Despina	Female	
Enceladus	Male	
Erinome	Female	
Fenrir	Male	
Gacrux	Female	
Iapetus	Male	
Kore	Female	
Laomedeia	Female	
Leda	Female	
Orus	Male	
Pulcherrima	Female	
Puck	Male	
Rasalgethi	Male	
Sadachbia	Male	
Sadaltager	Male	
Schedar	Male	
Sulafat	Female	
Umbriel	Male	
Vindemiatrix	Female	
Zephyr	Female	
Zubenelgenubi	Male	
Language availability
Gemini-TTS supports the following languages:

Language	BCP-47 Code	Launch Readiness
Arabic (Egypt)	ar-EG	GA
Dutch (Netherlands)	nl-NL	GA
English (India)	en-IN	GA
English (United States)	en-US	GA
French (France)	fr-FR	GA
German (Germany)	de-DE	GA
Hindi (India)	hi-IN	GA
Indonesian (Indonesia)	id-ID	GA
Italian (Italy)	it-IT	GA
Japanese (Japan)	ja-JP	GA
Korean (South Korea)	ko-KR	GA
Marathi (India)	mr-IN	GA
Polish (Poland)	pl-PL	GA
Portuguese (Brazil)	pt-BR	GA
Romanian (Romania)	ro-RO	GA
Russian (Russia)	ru-RU	GA
Spanish (Spain)	es-ES	GA
Tamil (India)	ta-IN	GA
Telugu (India)	te-IN	GA
Thai (Thailand)	th-TH	GA
Turkish (Turkey)	tr-TR	GA
Ukrainian (Ukraine)	uk-UA	GA
Vietnamese (Vietnam)	vi-VN	GA
Afrikaans (South Africa)	af-ZA	Preview
Albanian (Albania)	sq-AL	Preview
Amharic (Ethiopia)	am-ET	Preview
Arabic (World)	ar-001	Preview
Armenian (Armenia)	hy-AM	Preview
Azerbaijani (Azerbaijan)	az-AZ	Preview
Bangla (Bangladesh)	bn-bd	Preview
Basque (Spain)	eu-ES	Preview
Belarusian (Belarus)	be-BY	Preview
Bulgarian (Bulgaria)	bg-BG	Preview
Burmese (Myanmar)	my-MM	Preview
Catalan (Spain)	ca-ES	Preview
Cebuano (Philippines)	ceb-PH	Preview
Chinese, Mandarin (China)	cmn-cn	Preview
Chinese, Mandarin (Taiwan)	cmn-tw	Preview
Croatian (Croatia)	hr-HR	Preview
Czech (Czech Republic)	cs-CZ	Preview
Danish (Denmark)	da-DK	Preview
English (Australia)	en-AU	Preview
English (United Kingdom)	en-GB	Preview
Estonian (Estonia)	et-EE	Preview
Filipino (Philippines)	fil-PH	Preview
Finnish (Finland)	fi-FI	Preview
French (Canada)	fr-CA	Preview
Galician (Spain)	gl-ES	Preview
Georgian (Georgia)	ka-GE	Preview
Greek (Greece)	el-GR	Preview
Gujarati (India)	gu-IN	Preview
Haitian Creole (Haiti)	ht-HT	Preview
Hebrew (Israel)	he-IL	Preview
Hungarian (Hungary)	hu-HU	Preview
Icelandic (Iceland)	is-IS	Preview
Javanese (Java)	jv-JV	Preview
Kannada (India)	kn-IN	Preview
Konkani (India)	kok-in	Preview
Lao (Laos)	lo-LA	Preview
Latin (Vatican City)	la-VA	Preview
Latvian (Latvia)	lv-LV	Preview
Lithuanian (Lithuania)	lt-IT	Preview
Luxembourgish (Luxembourg)	lb-LU	Preview
Macedonian (North Macedonia)	mk-MK	Preview
Maithili (India)	mai-IN	Preview
Malagasy (Madagascar)	mg-MG	Preview
Malay (Malaysia)	ms-MY	Preview
Malayalam (India)	ml-IN	Preview
Mongolian (Mongolia)	mn-MN	Preview
Nepali (Nepal)	ne-NP	Preview
Norwegian, Bokmål (Norway)	nb-NO	Preview
Norwegian, Nynorsk (Norway)	nn-NO	Preview
Odia (India)	or-IN	Preview
Pashto (Afghanistan)	ps-AF	Preview
Persian (Iran)	fa-IR	Preview
Portuguese (Portugal)	pt-PT	Preview
Punjabi (India)	pa-IN	Preview
Serbian (Serbia)	sr-RS	Preview
Sindhi (India)	sd-IN	Preview
Sinhala (Sri Lanka)	si-LK	Preview
Slovak (Slovakia)	sk-SK	Preview
Slovenian (Slovenia)	sl-SI	Preview
Spanish (Latin America)	es-419	Preview
Spanish (Mexico)	es-MX	Preview
Swahili (Kenya)	sw-KE	Preview
Swedish (Sweden)	sv-SE	Preview
Urdu (Pakistan)	ur-PK	Preview
Regional availability
Gemini-TTS models are available in the following Google Cloud regions respectively:

Google Cloud zone	Launch readiness
global	GA
Supported output formats
The default response format is LINEAR16. Other supported formats include the following:

API method	Format
batch	ALAW, MULAW, MP3, OGG_OPUS, and PCM
streaming	Not supported
Use Gemini-TTS
Discover how to use Gemini-TTS models to synthesize single-speaker and multi-speaker speech.

Note: The size of the text field and the prompt field individually can be at most 900 bytes. While the total size of the prompt and text fields can be up to 1,800 bytes, each field must be a maximum of 900 bytes.
Note: To be able to use Gemini-TTS, aiplatform.endpoints.predict permission is required for the model endpoint. This permission can be granted with the roles/aiplatform.user role.
Before you begin
Before you can begin using Text-to-Speech, you must enable the API in the Google Cloud console by following steps:

Enable Text-to-Speech on a project.
Make sure billing is enabled for Text-to-Speech.
Set up authentication for your development environment.
Set up your Google Cloud project
Sign in to Google Cloud console

Go to the project selector page

You can either choose an existing project or create a new one. For more details about creating a project, see the Google Cloud documentation.

If you create a new project, a message appears informing you to link a billing account. If you are using a pre-existing project, make sure to enable billing

Learn how to confirm that billing is enabled for your project

Note: You must enable billing to use Text-to-Speech API, however, you won't be be charged unless you exceed the free quota. For more information about pricing, see the pricing page.
After you've selected a project and linked it to a billing account, you can enable the Text-to-Speech API. Go to the Search products and resources bar at the top of the page, and type in "speech". Select the Cloud Text-to-Speech API from the list of results.

To try Text-to-Speech without linking it to your project, choose the Try this API option. To enable the Text-to-Speech API for use with your project, click Enable.

Set up authentication for your development environment. For instructions, see Set up authentication for Text-to-Speech.

Perform synchronous single-speaker synthesis
Python
CURL


# Make sure to install gcloud cli, and sign in to your project.
# Make sure to use your PROJECT_ID value.
# The available models are gemini-2.5-flash-tts and gemini-2.5-pro-tts.
# To parse the JSON output and use it directly see the last line of the command.
# Requires JQ and ffplay library to be installed.
PROJECT_ID=YOUR_PROJECT_ID
curl -X POST \
  -H "Authorization: Bearer $(gcloud auth application-default print-access-token)" \
  -H "x-goog-user-project: $PROJECT_ID" \
  -H "Content-Type: application/json" \
-d '{
  "input": {
    "prompt": "Say the following in a curious way",
    "text": "OK, so... tell me about this [uhm] AI thing."
  },
  "voice": {
    "languageCode": "en-us",
    "name": "Kore",
    "model_name": "gemini-2.5-flash-tts"
  },
  "audioConfig": {
    "audioEncoding": "LINEAR16"
  }
}' \
  "https://texttospeech.googleapis.com/v1/text:synthesize" \
  | jq -r '.audioContent' | base64 -d | ffplay - -autoexit
Perform synchronous multi-speaker synthesis with freeform text input
Note: Speaker aliases must consist solely of alphanumeric characters, excluding whitespace.
Python
CURL


# Make sure to install gcloud cli, and sign in to your project.
# Make sure to use your PROJECT_ID value.
# The available models are gemini-2.5-flash-tts and gemini-2.5-pro-tts
# To parse the JSON output and use it directly see the last line of the command.
# Requires JQ and ffplay library to be installed.
# google-cloud-texttospeech minimum version 2.31.0 is required.
PROJECT_ID=YOUR_PROJECT_ID
curl -X POST \
  -H "Authorization: Bearer $(gcloud auth application-default print-access-token)" \
  -H "x-goog-user-project: $PROJECT_ID" \
  -H "Content-Type: application/json" \
-d '{
  "input": {
    "prompt": "Say the following as a conversation between friends.",
    "text": "Sam: Hi Bob, how are you?\\nBob: I am doing well, and you?"
  },
  "voice": {
    "languageCode": "en-us",
    "modelName": "gemini-2.5-flash-tts",
    "multiSpeakerVoiceConfig": {
      "speakerVoiceConfigs": [
        {
          "speakerAlias": "Sam",
          "speakerId": "Kore"
        },
        {
          "speakerAlias": "Bob",
          "speakerId": "Charon"
        }
      ]
    }
  },
  "audioConfig": {
    "audioEncoding": "LINEAR16",
    "sampleRateHertz": 24000
  }
}' \
  "https://texttospeech.googleapis.com/v1/text:synthesize" \
  | jq -r '.audioContent' | base64 -d | ffplay - -autoexit
Perform synchronous multi-speaker synthesis with structured text input
Multi-speaker with structured text input enables intelligent verbalization of text in a human-like way. For example, this kind of input is useful for addresses and dates. Freeform text input speaks the text exactly as written.

Note: The combined size of all lines of dialogue can be at most 900 bytes. While the total size of the prompt and dialogue can be up to 1,800 bytes, each prompt and dialogue field must be a maximum of 900 bytes. Speaker aliases must consist solely of alphanumeric characters, excluding whitespace.
Python
CURL


# Make sure to install gcloud cli, and sign in to your project.
# Make sure to use your PROJECT_ID value.
# The available models are gemini-2.5-flash-tts and gemini-2.5-pro-tts.
# To parse the JSON output and use it directly see the last line of the command.
# Requires JQ and ffplay library to be installed.
# google-cloud-texttospeech minimum version 2.31.0 is required.
PROJECT_ID=YOUR_PROJECT_ID
curl -X POST \
  -H "Authorization: Bearer $(gcloud auth application-default print-access-token)" \
  -H "x-goog-user-project: $PROJECT_ID" \
  -H "Content-Type: application/json" \
-d '{
  "input": {
    "prompt": "Say the following as a conversation between friends.",
    "multiSpeakerMarkup": {
      "turns": [
        {
          "speaker": "Sam",
          "text": "Hi Bob, how are you?"
        },
        {
          "speaker": "Bob",
          "text": "I am doing well, and you?"
        }
      ]
    }
  },
  "voice": {
    "languageCode": "en-us",
    "modelName": "gemini-2.5-flash-tts",
    "multiSpeakerVoiceConfig": {
      "speakerVoiceConfigs": [
        {
          "speakerAlias": "Sam",
          "speakerId": "Kore"
        },
        {
          "speakerAlias": "Bob",
          "speakerId": "Charon"
        }
      ]
    }
  },
  "audioConfig": {
    "audioEncoding": "LINEAR16",
    "sampleRateHertz": 24000
  }
}' \
  "https://texttospeech.googleapis.com/v1/text:synthesize" \
  | jq -r '.audioContent' | base64 -d | ffplay - -autoexit
Perform speech synthesis in Media Studio
You can use the Media Studio in the Google Google Cloud console to experiment with text-to-speech models. This provides a user interface for quickly generating, listening to synthesized audio and experimenting with different style instructions and parameters.

In the Google Google Cloud console, go to the Vertex AI Studio > Media Studio page.

Media Studio

Select Speech from the media drop-down.

In the text field, enter the text you want to synthesize into speech.

In the Settings pane, configure the following settings:

Model: Select the Text-to-Speech (TTS) model that you want to use, such as Gemini 2.5 Pro TTS. For more information about available models, see Text-to-Speech models.
Style instructions: Optional: Enter a text prompt that describes the selected speaking style, tone, and emotional delivery. This lets you to guide the model's performance beyond the default narration. For example: "Narrate in a calm, professional tone for a documentary.".
Language: Select the language and region of the input text. The model generates speech in the selected language and accent. For example, English (United States).
Voice: Choose a predefined voice for the narration. The list contains the available voices for the selected model and language, such as Acherner (Female).
Optional: Expand the Advanced options section to configure technical audio settings:

Audio encoding: Select the encoding for the output audio file. LINEAR16 is a lossless, uncompressed format suitable for high-quality audio processing. MULAW is also available for compressed audio output.
Audio sample rate: Select the sample rate in hertz (Hz). This determines the audio quality. Higher values like 44,100 Hz represent higher fidelity audio, equivalent to CD quality.
Speed: Adjust the speaking rate by moving the slider or entering a value. Values less than 1 slow down the speech, and values greater than 1 speed it up. The default is 1.
Volume gain (db): Adjust the volume of the output audio in decibels (dB). Positive values increase the volume, and negative values decrease it. The default is 0.
Click the send icon at the right of the text-box to generate the audio.

The generated audio appears in the media player. Click the play button to listen to the output. You can continue to adjust the settings, and generate new versions as needed.

Prompting Tips
Creating engaging and natural-sounding audio from text requires understanding the nuances of spoken language and translating them into script form. The following tips will help you craft scripts that sound authentic and capture the chosen tone.

The Three Levers of Speech Control
For the most predictable and nuanced results, ensure all three of the following components are consistent with your desired output.

Style Prompt The primary driver of the overall emotional tone and delivery. The prompt sets the context for the entire speech segment.

Example: You are an AI assistant speaking in a friendly and helpful tone.

Example: Narrate this in the calm, authoritative tone of a nature documentary narrator.

Text Content The semantic meaning of the words you are synthesizing. An evocative phrase that is emotionally consistent with the style prompt will produce much more reliable results than neutral text.

Good: A prompt for a scared tone works best with text like I think someone is in the house.

Less Effective: A prompt for a scared tone with text like The meeting is at 4 PM. will produce ambiguous results.

Markup Tags Bracketed tags like [sigh] are best used for injecting a specific, localized action or style modification, not for setting the overall tone. They work in concert with the style prompt and text content.

Markup Tag Guide
Our research shows that bracketed markup tags operate in one of three distinct modes. Understanding a tag's mode is key to using it effectively.

Mode 1: Non-Speech Sounds
The markup is replaced by an audible, non-speech vocalization (e.g., a sigh, a laugh). The tag itself is not spoken. These are excellent for adding realistic, human-like hesitations and reactions.

Tag	Behavior	Reliability	Guidance
[sigh]	Inserts a sigh sound.	High	The emotional quality of the sigh is influenced by the prompt.
[laughing]	Inserts a laugh.	High	For best results, use a specific prompt. e.g., a generic prompt may yield a laugh of shock, while "react with an amused laugh" creates a laugh of amusement.
[uhm]	Inserts a hesitation sound.	High	Useful for creating a more natural, conversational feel.
Mode 2: Style Modifiers
The markup is not spoken, but it modifies the delivery of the subsequent speech. The scope and duration of the modification can vary.

Tag	Behavior	Reliability	Guidance
[sarcasm]	Imparts a sarcastic tone on the subsequent phrase.	High	This tag is a powerful modifier. It demonstrates that abstract concepts can successfully steer the model's delivery.
[robotic]	Makes the subsequent speech sound robotic.	High	The effect can extend across an entire phrase. A supportive style prompt (e.g., "Say this in a robotic way") is still recommended for best results.
[shouting]	Increases the volume of the subsequent speech.	High	Most effective when paired with a matching style prompt (e.g., "Shout this next part") and text that implies yelling.
[whispering]	Decreases the volume of the subsequent speech.	High	Best results are achieved when the style prompt is also explicit (e.g., "now whisper this part as quietly as you can").
[extremely fast]	Increases the speed of the subsequent speech.	High	Ideal for disclaimers or fast-paced dialogue. Minimal prompt support needed.
Mode 3: Vocalized Markup (Adjectives)
The markup tag itself is spoken as a word, while also influencing the tone of the entire sentence. This behavior typically applies to emotional adjectives.

Warning: Because the tag itself is spoken, this mode is likely an undesired side effect for most use cases. Prefer using the Style Prompt to set these emotional tones instead.

Tag	Behavior	Reliability	Guidance
[scared]	The word "scared" is spoken, and the sentence adopts a scared tone.	High	Performance is highly dependent on text content. The phrase "I just heard a window break" produces a genuinely scared result. A neutral phrase produces a "spooky" but less authentic result.
[curious]	The word "curious" is spoken, and the sentence adopts a curious tone.	High	Use an inquisitive phrase to support the tag's intent.
[bored]	The word "bored" is spoken, and the sentence adopts a bored, monotone delivery.	High	Use with text that is mundane or repetitive for best effect.
Mode 4: Pacing and Pauses
These tags insert silence into the generated audio, giving you granular control over rhythm, timing, and pacing. Standard punctuation (commas, periods, semicolons) will also create natural pauses, but these tags offer more explicit control.

Tag	Behavior	Reliability	Guidance
[short pause]	Inserts a brief pause, similar to a comma (~250ms).	High	Use to separate clauses or list items for better clarity.
[medium pause]	Inserts a standard pause, similar to a sentence break (~500ms).	High	Effective for separating distinct sentences or thoughts.
[long pause]	Inserts a significant pause for dramatic effect (~1000ms+).	High	Use for dramatic timing. For example: "The answer is... [long pause] ...no." Avoid overuse, as it can sound unnatural.
Key Strategies for Reliable Results
Align All Three Levers For maximum predictability, ensure your Style Prompt, Text Content, and any Markup Tags are all semantically consistent and working toward the same goal.

Use Emotionally Rich Text Don't rely on prompts and tags alone. Give the model rich, descriptive text to work with. This is especially critical for nuanced emotions like sarcasm, fear, or excitement.

Write Specific, Detailed Prompts The more specific your style prompt, the more reliable the result. "React with an amused laugh" is better than just [laughing]. "Speak like a 1940s radio news announcer" is better than "Speak in an old-fashioned way."

Test and Verify New Tags The behavior of a new or untested tag is not always predictable. A tag you assume is a style modifier might be vocalized. Always test a new tag or prompt combination to confirm its behavior before deploying to production.