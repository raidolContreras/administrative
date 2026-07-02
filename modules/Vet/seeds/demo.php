<?php

declare(strict_types=1);

use Modules\Vet\Models\Appointment;
use Modules\Vet\Models\Owner;
use Modules\Vet\Models\Pet;

/** Datos de demostración del módulo Veterinaria (solo si está vacío) */
return static function (): void {
    if (Owner::count() > 0) {
        return;
    }

    $ana = Owner::create(['name' => 'Ana Torres', 'phone' => '555 010 2233', 'email' => 'ana@correo.mx', 'address' => 'Av. Reforma 120']);
    $luis = Owner::create(['name' => 'Luis Mendoza', 'phone' => '555 884 1902', 'email' => 'luis@correo.mx', 'address' => 'Calle 5 de Mayo 8']);
    $sofia = Owner::create(['name' => 'Sofía Ramírez', 'phone' => '555 771 6640', 'email' => null, 'address' => null]);

    $firulais = Pet::create(['owner_id' => $ana['id'], 'name' => 'Firulais', 'species' => 'perro', 'breed' => 'Labrador', 'sex' => 'M', 'birth_date' => '2022-03-15', 'weight_kg' => '28.50']);
    $michi = Pet::create(['owner_id' => $ana['id'], 'name' => 'Michi', 'species' => 'gato', 'breed' => 'Criollo', 'sex' => 'H', 'birth_date' => '2023-07-01', 'weight_kg' => '4.20']);
    $rocky = Pet::create(['owner_id' => $luis['id'], 'name' => 'Rocky', 'species' => 'perro', 'breed' => 'Bulldog', 'sex' => 'M', 'birth_date' => '2021-11-20', 'weight_kg' => '22.00']);
    Pet::create(['owner_id' => $sofia['id'], 'name' => 'Kiwi', 'species' => 'ave', 'breed' => 'Perico australiano', 'sex' => null, 'birth_date' => null, 'weight_kg' => '0.09']);

    Appointment::create(['pet_id' => $firulais['id'], 'scheduled_at' => date('Y-m-d') . ' 10:00:00', 'reason' => 'Vacuna anual', 'status' => 'programada']);
    Appointment::create(['pet_id' => $michi['id'], 'scheduled_at' => date('Y-m-d') . ' 12:30:00', 'reason' => 'Desparasitación', 'status' => 'programada']);
    Appointment::create(['pet_id' => $rocky['id'], 'scheduled_at' => date('Y-m-d', strtotime('+1 day')) . ' 09:00:00', 'reason' => 'Revisión de piel', 'status' => 'programada']);
};
