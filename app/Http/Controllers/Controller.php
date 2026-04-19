<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Item",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="nama_item", type="string", example="Sepatu Lari"),
 *     @OA\Property(property="harga", type="number", example=350000),
 *     @OA\Property(property="stok", type="integer", example=10),
 *     @OA\Property(property="deskripsi", type="string", example="Sepatu lari ringan"),
 *     @OA\Property(property="created_at", type="string", example="2026-04-19 10:00:00"),
 *     @OA\Property(property="updated_at", type="string", example="2026-04-19 10:00:00")
 * )
 */
abstract class Controller
{
    //
}
