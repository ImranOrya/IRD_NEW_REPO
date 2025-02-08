<?php

namespace App\Repositories\ngo;

interface NgoRepositoryInterface
{
    public function getNgoInit($locale, $ngo_id);
    public function getNgoDetail($locale, $ngo_id);
    /**
     * Retrieve NGO data.
     * cast is n
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function ngo();
    /**
     * Retrieve NGO Translation data.
     * cast is nt
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $locale
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function transJoin($query, $locale);
    /**
     * Retrieve NGO Status data.
     * cast is ns
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function statusJoin($query);

    /**
     * Retrieve NGO Status Translation data.
     * cast is stt
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $locale
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function statusTypeTransJoin($query, $locale);

    /**
     * Retrieve NGO TypeTrans Translation data.
     * cast is ntt
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $locale
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function typeTransJoin($query, $locale);

    /**
     * Retrieve NGO Director data.
     * cast is d
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function directorJoin($query);

    /**
     * Retrieve NGO Director Translation data.
     * cast is dt
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $locale
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function directorTransJoin($query, $locale);
    /**
     * Retrieve NGO Email data.
     * cast is e
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function emailJoin($query);
    /**
     * Retrieve NGO Contact data.
     * cast is c
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function contactJoin($query);
    /**
     * Retrieve NGO Contact data.
     * cast is a
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function addressJoin($query);
}
