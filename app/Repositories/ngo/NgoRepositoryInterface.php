<?php

namespace App\Repositories\ngo;

interface NgoRepositoryInterface
{
    /**
     * Retrieve NGO data.
     * cast is n
     * @param string $id
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function ngo($id = null);
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
     * Retrieve NGO all Translation data.
     * cast is nt
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function transJoinLocales($query);
    /**
     * Retrieve NGO Status data.
     * cast is ns
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function statusJoin($query);

    /**
     * Retrieve NGO Status All Translations.
     * cast is ns
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */

    public function statusJoinAll($query);

    /**
     * Retrieve NGO Status Type Translation data.
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
    /**
     * Joins the last agreement.
     * cast is ag
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function agreementJoin($query);
    /**
     * Returns agreement documents.
     * cast is ag
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function agreementDocuments($query, $agreement_id, $locale);
    /**
     * Retrieve NGO data when registered by IRD.
     * 
     *
     * @param string $ngo_id
     * @param string $locale
     * @return array
     */
    public function startRegisterFormInfo($ngo_id, $locale);
    /**
     * Retrieve NGO data when registeration is completed.
     * 
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $ngo_id
     * @param string $locale
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function afterRegisterFormInfo($ngo_id, $locale);
    /**
     * Retrieve NGO all statuses along with tanslations.
     * 
     *
     * @param string $ngo_id
     * @param string $locale
     * @return \App\Repositories\ngo\NgoRepositoryInterface|\Illuminate\Database\Query\Builder
     */
    public function statuses($ngo_id, $locale);
}
