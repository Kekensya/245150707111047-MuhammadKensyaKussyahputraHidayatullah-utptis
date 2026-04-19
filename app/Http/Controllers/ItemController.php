<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenApi\Annotations as OA;
class ItemController extends Controller
{
    private string $dataPath;

    public function __construct()
    {
        $this->dataPath = storage_path('app/items.json');
        if (!file_exists($this->dataPath)) {
            file_put_contents($this->dataPath, json_encode([], JSON_PRETTY_PRINT));
        }
    }

    private function readItems(): array
    {
        return json_decode(file_get_contents($this->dataPath), true) ?? [];
    }

    private function writeItems(array $items): void
    {
        file_put_contents($this->dataPath, json_encode(array_values($items), JSON_PRETTY_PRINT));
    }

    private function findItem(array $items, int $id): int|false
    {
        foreach ($items as $index => $item) {
            if ((int) $item['id'] === $id) return $index;
        }
        return false;
    }

    /**
     * @OA\Get(
     *     path="/items",
     *     summary="Menampilkan semua item",
     *     tags={"Items"},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar semua item",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Item"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $items = $this->readItems();
        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/items/{id}",
     *     summary="Menampilkan item berdasarkan ID",
     *     tags={"Items"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Item ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Item")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Item tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Item dengan ID 99 tidak Ditemukan")
     *         )
     *     )
     * )
     */
    public function show(int $id)
    {
        $items = $this->readItems();
        $index = $this->findItem($items, $id);

        if ($index === false) {
            return response()->json([
                'success' => false,
                'message' => "Item dengan ID {$id} tidak Ditemukan",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $items[$index],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/items",
     *     summary="Membuat item baru",
     *     tags={"Items"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama_item","harga"},
     *             @OA\Property(property="nama_item", type="string", example="Sepatu Lari"),
     *             @OA\Property(property="harga", type="number", example=350000),
     *             @OA\Property(property="stok", type="integer", example=10),
     *             @OA\Property(property="deskripsi", type="string", example="Sepatu lari ringan")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Item berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Item berhasil dibuat"),
     *             @OA\Property(property="data", ref="#/components/schemas/Item")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_item' => 'required|string|max:255',
            'harga'     => 'required|numeric|min:0',
            'stok'      => 'nullable|integer|min:0',
            'deskripsi' => 'nullable|string',
        ]);

        $items = $this->readItems();

        $newId = count($items) > 0
            ? max(array_column($items, 'id')) + 1
            : 1;

        $newItem = [
            'id'         => $newId,
            'nama_item'  => $validated['nama_item'],
            'harga'      => (float) $validated['harga'],
            'stok'       => $validated['stok'] ?? 0,
            'deskripsi'  => $validated['deskripsi'] ?? null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ];

        $items[] = $newItem;
        $this->writeItems($items);

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dibuat',
            'data'    => $newItem,
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/items/{id}",
     *     summary="Mengedit seluruh data item (replace)",
     *     tags={"Items"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nama_item","harga","stok","deskripsi"},
     *             @OA\Property(property="nama_item", type="string", example="Sepatu Baru"),
     *             @OA\Property(property="harga", type="number", example=400000),
     *             @OA\Property(property="stok", type="integer", example=5),
     *             @OA\Property(property="deskripsi", type="string", example="Deskripsi baru")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Item berhasil diupdate"),
     *     @OA\Response(response=404, description="Item tidak ditemukan")
     * )
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'nama_item' => 'required|string|max:255',
            'harga'     => 'required|numeric|min:0',
            'stok'      => 'required|integer|min:0',
            'deskripsi' => 'required|string',
        ]);

        $items = $this->readItems();
        $index = $this->findItem($items, $id);

        if ($index === false) {
            return response()->json([
                'success' => false,
                'message' => "Item dengan ID {$id} tidak Ditemukan",
            ], 404);
        }

        $items[$index] = [
            'id'         => $id,
            'nama_item'  => $validated['nama_item'],
            'harga'      => (float) $validated['harga'],
            'stok'       => $validated['stok'],
            'deskripsi'  => $validated['deskripsi'],
            'created_at' => $items[$index]['created_at'],
            'updated_at' => now()->toDateTimeString(),
        ];

        $this->writeItems($items);

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil diupdate (PUT)',
            'data'    => $items[$index],
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/items/{id}",
     *     summary="Mengedit sebagian data item",
     *     tags={"Items"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nama_item", type="string", example="Nama Baru"),
     *             @OA\Property(property="harga", type="number", example=500000),
     *             @OA\Property(property="stok", type="integer", example=20),
     *             @OA\Property(property="deskripsi", type="string", example="Desc baru")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Item berhasil diupdate sebagian"),
     *     @OA\Response(response=404, description="Item tidak ditemukan")
     * )
     */
    public function patch(Request $request, int $id)
    {
        $validated = $request->validate([
            'nama_item' => 'sometimes|string|max:255',
            'harga'     => 'sometimes|numeric|min:0',
            'stok'      => 'sometimes|integer|min:0',
            'deskripsi' => 'sometimes|string',
        ]);

        $items = $this->readItems();
        $index = $this->findItem($items, $id);

        if ($index === false) {
            return response()->json([
                'success' => false,
                'message' => "Item dengan ID {$id} tidak Ditemukan",
            ], 404);
        }

        $items[$index] = array_merge($items[$index], $validated, [
            'updated_at' => now()->toDateTimeString(),
        ]);

        $this->writeItems($items);

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil diupdate sebagian (PATCH)',
            'data'    => $items[$index],
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/items/{id}",
     *     summary="Menghapus item berdasarkan ID",
     *     tags={"Items"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item berhasil dihapus"),
     *     @OA\Response(response=404, description="Item tidak ditemukan")
     * )
     */
    public function destroy(int $id)
    {
        $items = $this->readItems();
        $index = $this->findItem($items, $id);

        if ($index === false) {
            return response()->json([
                'success' => false,
                'message' => "Item dengan ID {$id} tidak Ditemukan",
            ], 404);
        }

        $deleted = $items[$index];
        array_splice($items, $index, 1);
        $this->writeItems($items);

        return response()->json([
            'success' => true,
            'message' => 'Item berhasil dihapus',
            'data'    => $deleted,
        ]);
    }
}
