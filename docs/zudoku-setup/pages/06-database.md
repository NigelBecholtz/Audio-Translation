# 06 – Database

## Models

| Model | Purpose |
|-------|---------|
| `User` | Auth, credits, is_admin, audioFiles, textToAudioFiles, payments |
| `AudioFile` | Uploaded audio, transcription, translation, TTS, status, processing stages |
| `TextToAudio` | Text-to-audio jobs, text, language, voice, style_instruction |
| `Translation` | Additional translations per audio file |
| `AudioTranslation` | Audio translation variants with processing fields |
| `Payment` | Stripe payments |
| `CreditTransaction` | Credit history (add/remove, usage) |
| `CsvTranslationJob` | CSV translation jobs, status, output path |
| `StyleInstructionPreset` | TTS style presets |

## Key Migrations

| Migration | Purpose |
|-----------|---------|
| `create_users_table` | Users, credits, is_admin |
| `create_audio_files_table` | Audio uploads |
| `add_user_id_to_audio_files_table` | Link audio to user |
| `create_translations_table` | Additional translations |
| `create_payments_table` | Stripe payments |
| `create_credit_transactions_table` | Credit history |
| `add_admin_role_to_users_table` | is_admin |
| `create_text_to_audio_table` | Text-to-audio |
| `add_voice_to_audio_files_table` | Voice selection |
| `add_style_instruction_to_audio_files_table` | TTS style |
| `add_processing_stage_to_audio_files_table` | Progress tracking |
| `add_pending_approval_to_audio_files_status` | Approval workflow |
| `add_pending_tts_approval_to_audio_files_status` | TTS approval |
| `create_audio_translations_table` | Audio translation variants |
| `create_csv_translation_jobs_table` | CSV jobs |
| `create_style_instruction_presets_table` | Style presets |
| `create_jobs_table` | Laravel queue jobs |
| `create_cache_table` | Cache |

## Relationships

- `User` → `audioFiles`, `textToAudioFiles`, `payments`, `creditTransactions`
- `AudioFile` → `user`, `translations`, `audioTranslations`
- `TextToAudio` → `user`
- `Payment` → `user`
- `CreditTransaction` → `user`
