<?php namespace App\DTIStore\Repositories;

interface RoleInterface
{
    public function create(array $data);

    public function find($id);

    public function findByCode($code);

    public function getAll();

    public function filter(array $filter);

    public function update($id, $data);

    public function delete($id);

    public function destroy($id);

    public function isDeleted($id);

    public function getHighRankingRoles($code, $include);
}