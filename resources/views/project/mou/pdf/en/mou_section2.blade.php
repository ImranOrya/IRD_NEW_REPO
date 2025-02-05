<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Document</title>
</head>
<style>
  .min-logo {
    height: 70px;
    width: 70px;
    float: right;
    text-align: right;
    margin: 0;
    padding: 0;
    margin-top: -40px;
  }

  .moph-logo {
    height: 70px;
    width: 70px;
    float: left;
    text-align: left;
    margin: 0;
    padding: 0;
    margin-top: -40px;
  }

  .header-logo {
    height: 50px;
    width: 100%;
    margin: 0;
    padding: 0;
    margin-top: 120px;
  }

  .min-logo-div {
    width: 100%;
    text-align: center;
    margin: 0;
    padding: 0;



  }

  * {
    margin: 0;
    padding: 0;
  }

  .header-text-cont {

    padding: 0;
    margin: 0;
    margin-top: 80px;
  }

  .header-text {

    padding: 0;
    margin: 0;
  }

  .first-page-text {
    text-align: center;
    /* font-size: 30px; */

  }

  .page {
    page-break-after: always;
    /* Add a page break after each pair of student cards */
  }

  .min-contents p {
    font-size: 15px;

  }

  .content-text {}


  .irddirector {

    float: left;
    margin: 0;
    padding: 0;
  }

  .ngodirector {
    float: right;
    margin-right: -40px;
    padding: 0;
  }

  .sing-page {
    width: 100%;
    align-items: center;
    margin-top: 50px;
    padding-top: 50px;
    /* border:1px solid red; */


  }

  .ngodirector {
    padding: 0px;
    margin-top: 10px;
    /* margin-right:10px; */
    width: 50%;
    height: 50px;
    float: right;
  }

  .irddirector {
    padding: 0px;
    margin-top: 10px;
    /* margin-right:10px; */
    width: 50%;
    height: 50px;
    float: left;
  }
</style>

<body>

  <div class="min-logo-div">
    <img src="{{ storage_path('app/public/images/emart.png') }}" class="min-logo" alt="">
    <img src="{{ storage_path('app/public/images/moph.png') }}" class="moph-logo" alt="">
    <img src="{{ storage_path('app/public/images/header.png') }}" class="header-logo" alt="">



    <div class="header-text-cont">

      <h5 class="header-text">Ministry Of Public Health
      </h5>
      <h5 class="header-text">International Relations Directorate
      </h5>
      <h5 class="header-text">Islamic Emirate Of Afghanistan</h5>
      <br>
    </div>


  </div>

  <div class="min-contents">

    <h1 class="first-page-text">
      Memorandum of Understanding
    </h1>
    <h2 class="first-page-text">
      Between
      <br>
      The Ministry of Public Health
      <br>
      {{ ($ngo_name) }}
    </h2>




    <div class="page"></div>


    <p class="content-text">
      Date and place of establishment: {{ $establishment_date }} , {{ $place_of_establishment }}
    </p>
    <p class="content-text">
      Ministry of Economy Registration number: {{ $ministry_economy_no }}
    </p>

    <p class="content-text">
      Organization’s general objectives: {{ $general_objective }}
    </p>
    <p class="content-text">
      Organization’s objectives in Afghanistan: {{ $afganistan_objective }}
    </p>
    <p class="content-text">
      Organization’s Mission:{{ $mission }}
    </p>
    <p class="content-text">
      Organization’s Vision: {{ $vission }}
    </p>

    <p class="content-text">
      <b> This registration obliges both parties to respect the following principles:</b>
      <br>
      1-The registration is valid for one year and extendable in case there is no complaint against the
      NGO and no violation by the NGO in implementing projects in the health sector.
      <br>
      2-This document does not serve as a permission for NGOs to perform health activities. All NGOs
      must obtain permission/NOC from the MoPH and sign MoU with the MoPH before the implementation of their health project.
      <br>
      3-The NGO shall officially consult with the Ministry of Public Health about the selection of site
      before the implementation of any health project. It should sign MoU with the Ministry of Public
      Health prior to the project implementation so the provision of services is fully legal. Failure to do
      so will cause disruption in their registration/renewal of registration.

      <br>
      4-Based on this registration, the NGOs cannot perform the health activity prior to signing MoU
      between MoPH and NGOs.
      <br>
      5-Agency will deliver all components outlined in the Basic Package of Health Services (BPHS)
      and/or Essential Package of Hospital Services (EPHS) after obtaining the NOC and signing MoU
      with the Ministry.
      <br>
      6- If an NGO delivers a vertical program or focuses on certain components of BPHS/EPHS, they
      must obtain official permission from the leadership of the MoPH.
      <br>
      7-Agency will follow all health policies of the MoPH.
      8-Agency will comply in reporting data and information based on the MoPH HMIS and standards.
      <br>
      9-Agency will participate in coordination mechanisms at the central and provincial levels.
      <br>
      10-Project information including staffing, salary scale and budgets will be made available to MoPH
      in a timely manner upon request.
      <br>
      11-Agency will agree to participate in the national monitoring and evaluation system operated by
      MoPH and a third party.
      <br>
      12-Agency staff will conduct themselves and implement their projects in a manner that is culturally
      appropriate
      <br>
      13-Agency will keep the number of the expatriate staff to the minimum required; based on needs
      and competency.
      <br>
      14-Agency will contribute towards training relevant Afghan staff based on a training need assessment for future sustainability.
      <br>
      15-The MoPH has the right to monitor & evaluate the agencies project activities at any time during
      the duration of this registration.
      <br>
      16- MoPH and agency will respect the principles of equity, efficiency and non-discrimination on
      the basis of ethnicity, gender or any other factor outlined in the National Development Budget
      Narrative and National Health Strategy
      <br>
      17-In addition, under-served areas should be prioritized for service delivery even if these areas
      present more difficulties logistically.
      <br>
      18-MoPH and agency agree to work together in a collaborative and transparent partnership that

      is characterized by mutual respect.
      <br>
      19-Any disputes will be resolved through discussion with Provincial Health Coordination Committee (PHCC) or central MoPH representatives. MoPH reserves the right to appoint a third party to
      assist in resolving any disputes.
      <br>
      20-The MoPH has the right to terminate this registration at any time for non-compliance with any
      of the above provisions of this registration.
      <br>
      21-NGO shall observe and implement the principles and laws of the Islamic Emirate of
      Afghanistan.



    </p>

  </div>
  <div class="page"></div>




  <div class="sing-page">
    <div class="irddirector">
      On behalf of MoPH
      <br>
      {{$ird_director}}
      <br>
      Director of International Relations
      <br>
      <br>
      <br>

      Signature:................................
    </div>
    <div class="ngodirector">
      On behalf of NGO
      <br>
      {{ $director }}
      <br>
      Director of {{ $ngo_name }}
      <br>
      <br>
      <br>
      Signature:................................

    </div>

  </div>




</body>

</html>