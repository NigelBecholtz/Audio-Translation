<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportController extends Controller
{
    /**
     * Export audio translations to CSV
     */
    public function exportAudioTranslations()
    {
        $user = auth()->user();
        $audioFiles = $user->audioFiles()->orderBy('created_at', 'desc')->get();

        $filename = 'audio-translations-' . date('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($audioFiles) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'ID',
                'Bestandsnaam',
                'Brontaal',
                'Doeltaal',
                'Stem',
                'Status',
                'Grootte (KB)',
                'Aangemaakt op',
                'Bijgewerkt op',
                'Transcriptie',
                'Vertaalde tekst',
            ], ';');

            // Data rows
            foreach ($audioFiles as $audioFile) {
                fputcsv($file, [
                    $audioFile->id,
                    $audioFile->original_filename,
                    strtoupper($audioFile->source_language),
                    strtoupper($audioFile->target_language),
                    ucfirst($audioFile->voice),
                    ucfirst($audioFile->status),
                    number_format($audioFile->file_size / 1024, 2),
                    $audioFile->created_at->format('Y-m-d H:i:s'),
                    $audioFile->updated_at->format('Y-m-d H:i:s'),
                    $audioFile->transcription ?? '',
                    $audioFile->translated_text ?? '',
                ], ';');
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export text-to-audio conversions to CSV
     */
    public function exportTextToAudio()
    {
        $user = auth()->user();
        $textToAudioFiles = $user->textToAudioFiles()->orderBy('created_at', 'desc')->get();

        $filename = 'text-to-audio-' . date('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($textToAudioFiles) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'ID',
                'Taal',
                'Stem',
                'Status',
                'Tekst lengte',
                'Aangemaakt op',
                'Bijgewerkt op',
                'Tekst inhoud',
            ], ';');

            // Data rows
            foreach ($textToAudioFiles as $textToAudio) {
                fputcsv($file, [
                    $textToAudio->id,
                    strtoupper($textToAudio->language),
                    ucfirst($textToAudio->voice),
                    ucfirst($textToAudio->status),
                    strlen($textToAudio->text_content),
                    $textToAudio->created_at->format('Y-m-d H:i:s'),
                    $textToAudio->updated_at->format('Y-m-d H:i:s'),
                    $textToAudio->text_content,
                ], ';');
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export credit transactions to CSV
     */
    public function exportCreditHistory()
    {
        $user = auth()->user();
        $transactions = $user->creditTransactions()->orderBy('created_at', 'desc')->get();

        $filename = 'credit-history-' . date('Y-m-d-His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'ID',
                'Type',
                'Bedrag',
                'Beschrijving',
                'Saldo na transactie',
                'Datum',
            ], ';');

            // Data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->type,
                    number_format($transaction->amount, 2),
                    $transaction->description,
                    number_format($transaction->balance_after, 2),
                    $transaction->created_at->format('Y-m-d H:i:s'),
                ], ';');
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }
}