<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SensitiveClientData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EncryptClientContactDataCommand extends Command
{
    protected $signature = 'aurobarber:encrypt-client-contact-data';

    protected $description = 'Encrypt legacy client phone and email values and populate blind indexes.';

    public function handle(): int
    {
        $clientCount = 0;
        DB::table('barbershop_clients')
            ->select(['id', 'phone', 'phone_hash'])
            ->orderBy('id')
            ->chunkById(200, function ($clients) use (&$clientCount): void {
                foreach ($clients as $client) {
                    $plainPhone = SensitiveClientData::decrypt($client->phone);
                    $encryptedPhone = SensitiveClientData::isEncrypted($client->phone)
                        ? $client->phone
                        : SensitiveClientData::encrypt($plainPhone);
                    $phoneHash = SensitiveClientData::phoneHash($plainPhone);

                    if ($encryptedPhone !== $client->phone || $phoneHash !== $client->phone_hash) {
                        DB::table('barbershop_clients')
                            ->where('id', $client->id)
                            ->update([
                                'phone' => $encryptedPhone,
                                'phone_hash' => $phoneHash,
                                'updated_at' => now(),
                            ]);
                        $clientCount++;
                    }
                }
            });

        $appointmentCount = 0;
        DB::table('appointments')
            ->select(['id', 'client_phone', 'client_phone_hash', 'client_email', 'client_email_hash'])
            ->orderBy('id')
            ->chunkById(200, function ($appointments) use (&$appointmentCount): void {
                foreach ($appointments as $appointment) {
                    $plainPhone = SensitiveClientData::decrypt($appointment->client_phone);
                    $plainEmail = SensitiveClientData::decrypt($appointment->client_email);
                    $encryptedPhone = SensitiveClientData::isEncrypted($appointment->client_phone)
                        ? $appointment->client_phone
                        : SensitiveClientData::encrypt($plainPhone);
                    $encryptedEmail = SensitiveClientData::isEncrypted($appointment->client_email)
                        ? $appointment->client_email
                        : SensitiveClientData::encrypt($plainEmail);
                    $phoneHash = SensitiveClientData::phoneHash($plainPhone);
                    $emailHash = SensitiveClientData::emailHash($plainEmail);

                    if (
                        $encryptedPhone !== $appointment->client_phone
                        || $encryptedEmail !== $appointment->client_email
                        || $phoneHash !== $appointment->client_phone_hash
                        || $emailHash !== $appointment->client_email_hash
                    ) {
                        DB::table('appointments')
                            ->where('id', $appointment->id)
                            ->update([
                                'client_phone' => $encryptedPhone,
                                'client_phone_hash' => $phoneHash,
                                'client_email' => $encryptedEmail,
                                'client_email_hash' => $emailHash,
                                'updated_at' => now(),
                            ]);
                        $appointmentCount++;
                    }
                }
            });

        $this->info("Encrypted or indexed {$clientCount} client record(s) and {$appointmentCount} appointment(s).");

        return self::SUCCESS;
    }
}
