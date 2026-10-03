<?php
namespace carry0987\Sanite\Interfaces;

interface DataCreateInterface
{
    public function createSingleData(array $queryArray, array $dataArray, bool $getAutoIncrement = false, ?string $sequenceName = null): array|bool;
    public function createSingleReturning(array $queryArray, array $dataArray): array;
    public function createMultipleData(array $queryArray, array $dataArray): bool;
}
