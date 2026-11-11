<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * TÜİK official CPI 12-month-average values paired with the actual release dates
     * from its National Data Release Calendar. Each period is the following renewal month.
     * @var array<int, array{string,string,string,string}>
     */
    private const RATES = [
        ["2019-01-01", "16.33", "2019-01-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2018-30848&dil=1"],
        ["2019-02-01", "17.16", "2019-02-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ocak-2019-30849&dil=1"],
        ["2019-03-01", "17.93", "2019-03-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-%C5%9Eubat-2019-30850&dil=1"],
        ["2019-04-01", "18.70", "2019-04-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Mart-2019-30851&dil=1"],
        ["2019-05-01", "19.39", "2019-05-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Nisan-2019-30852&dil=1"],
        ["2019-06-01", "19.91", "2019-06-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-May%C4%B1s-2019-30853&dil=1"],
        ["2019-07-01", "19.88", "2019-07-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Haziran-2019-30854&dil=1"],
        ["2019-08-01", "19.91", "2019-08-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Temmuz-2019-30855&dil=1"],
        ["2019-09-01", "19.62", "2019-09-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-A%C4%9Fustos-2019-30856&dil=1"],
        ["2019-10-01", "18.27", "2019-10-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Eyl%C3%BCl-2019-30857&dil=1"],
        ["2019-11-01", "16.81", "2019-11-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ekim-2019-30858&dil=1"],
        ["2019-12-01", "15.87", "2019-12-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Kas%C4%B1m-2019-30859&dil=1"],
        ["2020-01-01", "15.18", "2020-01-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2019-33861&dil=1"],
        ["2020-02-01", "14.52", "2020-02-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ocak-2020-33862&dil=1"],
        ["2020-03-01", "13.94", "2020-03-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-%C5%9Eubat-2020-33863&dil=1"],
        ["2020-04-01", "13.33", "2020-04-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Mart-2020-33864&dil=1"],
        ["2020-05-01", "12.66", "2020-05-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Nisan-2020-33865&dil=1"],
        ["2020-06-01", "12.10", "2020-06-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-May%C4%B1s-2020-33866&dil=1"],
        ["2020-07-01", "11.88", "2020-07-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Haziran-2020-33867&dil=1"],
        ["2020-08-01", "11.51", "2020-08-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Temmuz-2020-33868&dil=1"],
        ["2020-09-01", "11.27", "2020-09-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-A%C4%9Fustos-2020-33869&dil=1"],
        ["2020-10-01", "11.47", "2020-10-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Eyl%C3%BCl-2020-33870&dil=1"],
        ["2020-11-01", "11.74", "2020-11-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ekim-2020-33871&dil=1"],
        ["2020-12-01", "12.04", "2020-12-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Kas%C4%B1m-2020-33872&dil=1"],
        ["2021-01-01", "12.28", "2021-01-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2020-37378&dil=1"],
        ["2021-02-01", "12.53", "2021-02-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ocak-2021-37379&dil=1"],
        ["2021-03-01", "12.81", "2021-03-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-%C5%9Eubat-2021-37380&dil=1"],
        ["2021-04-01", "13.18", "2021-04-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Mart-2021-37381&dil=1"],
        ["2021-05-01", "13.70", "2021-05-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Nisan-2021-37382&dil=1"],
        ["2021-06-01", "14.13", "2021-06-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-May%C4%B1s-2021-37383&dil=1"],
        ["2021-07-01", "14.55", "2021-07-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Haziran-2021-37384&dil=1"],
        ["2021-08-01", "15.15", "2021-08-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Temmuz-2021-37385&dil=1"],
        ["2021-09-01", "15.78", "2021-09-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-A%C4%9Fustos-2021-37386&dil=1"],
        ["2021-10-01", "16.42", "2021-10-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Eyl%C3%BCl-2021-37387&dil=1"],
        ["2021-11-01", "17.09", "2021-11-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ekim-2021-37388&dil=1"],
        ["2021-12-01", "17.71", "2021-12-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Kas%C4%B1m-2021-37389&dil=1"],
        ["2022-01-01", "19.60", "2022-01-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2021-45789&dil=1"],
        ["2022-02-01", "22.58", "2022-02-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ocak-2022-45790&dil=1"],
        ["2022-03-01", "25.98", "2022-03-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-%C5%9Eubat-2022-45791&dil=1"],
        ["2022-04-01", "29.88", "2022-04-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Mart-2022-45792&dil=1"],
        ["2022-05-01", "34.46", "2022-05-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Nisan-2022-45793&dil=1"],
        ["2022-06-01", "39.33", "2022-06-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-May%C4%B1s-2022-45794&dil=1"],
        ["2022-07-01", "44.54", "2022-07-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Haziran-2022-45795&dil=1"],
        ["2022-08-01", "49.65", "2022-08-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Temmuz-2022-45796&dil=1"],
        ["2022-09-01", "54.69", "2022-09-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-A%C4%9Fustos-2022-45797&dil=1"],
        ["2022-10-01", "59.91", "2022-10-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Eyl%C3%BCl-2022-45798&dil=1"],
        ["2022-11-01", "65.26", "2022-11-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ekim-2022-45799&dil=1"],
        ["2022-12-01", "70.36", "2022-12-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Kas%C4%B1m-2022-45800&dil=1"],
        ["2023-01-01", "72.31", "2023-01-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2022-49651&dil=1"],
        ["2023-02-01", "72.45", "2023-02-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ocak-2023-49655&dil=1"],
        ["2023-03-01", "71.83", "2023-03-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-%C5%9Eubat-2023-49656&dil=1"],
        ["2023-04-01", "70.20", "2023-04-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Mart-2023-49652&dil=1"],
        ["2023-05-01", "67.20", "2023-05-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Nisan-2023-49653&dil=1"],
        ["2023-06-01", "63.72", "2023-06-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-May%C4%B1s-2023-49654&dil=1"],
        ["2023-07-01", "59.95", "2023-07-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Haziran-2023-49658&dil=1"],
        ["2023-08-01", "57.45", "2023-08-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Temmuz-2023-49649&dil=1"],
        ["2023-09-01", "56.28", "2023-09-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-A%C4%9Fustos-2023-49650&dil=1"],
        ["2023-10-01", "55.30", "2023-10-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Eyl%C3%BCl-2023-49659&dil=1"],
        ["2023-11-01", "54.26", "2023-11-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ekim-2023-49660&dil=1"],
        ["2023-12-01", "53.40", "2023-12-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Kas%C4%B1m-2023-49661&dil=1"],
        ["2024-01-01", "53.86", "2024-01-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2023-49657&dil=1"],
        ["2024-02-01", "54.72", "2024-02-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ocak-2024-53622&dil=1"],
        ["2024-03-01", "55.91", "2024-03-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-%C5%9Eubat-2024-53623&dil=1"],
        ["2024-04-01", "57.50", "2024-04-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Mart-2024-53613&dil=1"],
        ["2024-05-01", "59.64", "2024-05-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Nisan-2024-53614&dil=1"],
        ["2024-06-01", "62.51", "2024-06-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-May%C4%B1s-2024-53615&dil=1"],
        ["2024-07-01", "65.07", "2024-07-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Haziran-2024-53616&dil=1"],
        ["2024-08-01", "65.93", "2024-08-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Temmuz-2024-53617&dil=1"],
        ["2024-09-01", "64.91", "2024-09-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-A%C4%9Fustos-2024-53624&dil=1"],
        ["2024-10-01", "63.47", "2024-10-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Eyl%C3%BCl-2024-53618&dil=1"],
        ["2024-11-01", "62.02", "2024-11-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ekim-2024-53619&dil=1"],
        ["2024-12-01", "60.45", "2024-12-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Kas%C4%B1m-2024-53620&dil=1"],
        ["2025-01-01", "58.51", "2025-01-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2024-53621&dil=1"],
        ["2025-02-01", "56.35", "2025-02-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ocak-2025-54176&dil=1"],
        ["2025-03-01", "53.83", "2025-03-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-%C5%9Eubat-2025-54177&dil=1"],
        ["2025-04-01", "51.26", "2025-04-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Mart-2025-54178&dil=1"],
        ["2025-05-01", "48.73", "2025-05-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Nisan-2025-54179&dil=1"],
        ["2025-06-01", "45.80", "2025-06-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-May%C4%B1s-2025-54180&dil=1"],
        ["2025-07-01", "43.23", "2025-07-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Haziran-2025-54181&dil=1"],
        ["2025-08-01", "41.13", "2025-08-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Temmuz-2025-54182&dil=1"],
        ["2025-09-01", "39.62", "2025-09-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-A%C4%9Fustos-2025-54183&dil=1"],
        ["2025-10-01", "38.36", "2025-10-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Eyl%C3%BCl-2025-54184&dil=1"],
        ["2025-11-01", "37.15", "2025-11-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ekim-2025-54185&dil=1"],
        ["2025-12-01", "35.91", "2025-12-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Kas%C4%B1m-2025-54186&dil=1"],
        ["2026-01-01", "34.88", "2026-01-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Aral%C4%B1k-2025-58294&dil=1"],
        ["2026-02-01", "33.98", "2026-02-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ocak-2026-58293&dil=1"],
        ["2026-03-01", "33.39", "2026-03-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-%C5%9Eubat-2026-58287&dil=1"],
        ["2026-04-01", "32.82", "2026-04-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Mart-2026-58295&dil=1"],
        ["2026-05-01", "32.43", "2026-05-04", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Nisan-2026-58288&dil=1"],
        ["2026-06-01", "32.24", "2026-06-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-May%C4%B1s-2026-58296&dil=1"],
        ["2026-07-01", "32.03", "2026-07-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Haziran-2026-58289&dil=1"],
        ["2026-08-01", "31.90", "2026-08-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Temmuz-2026-58297&dil=1"],
        ["2026-09-01", "31.79", "2026-09-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-A%C4%9Fustos-2026-58290&dil=1"],
        ["2026-10-01", "31.49", "2026-10-05", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Eyl%C3%BCl-2026-58298&dil=1"],
        ["2026-11-01", "31.23", "2026-11-03", "https://veriportali.tuik.gov.tr/Bulten/Index?p=T%C3%BCketici-Fiyat-Endeksi-Ekim-2026-58291&dil=1"],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::RATES as [$period, $rate, $publishedOn, $sourceUrl]) {
                $existing = DB::table('rent_period_rates')->where('period', $period)->first(['id', 'rate', 'published_on']);
                if ($existing) {
                    if (abs((float) $existing->rate - (float) $rate) > 0.0001 || $existing->published_on !== $publishedOn) {
                        throw new \RuntimeException("Existing official rent rate conflicts with the historical TÜİK series for {$period}.");
                    }

                    if (! DB::table('rent_rate_logs')->where('rate_id', $existing->id)->exists()) {
                        DB::table('rent_rate_logs')->insert([
                            'rate_id' => $existing->id,
                            'snapshot' => json_encode([
                                'period' => $period, 'rate' => $rate, 'source_url' => $sourceUrl,
                                'published_on' => $publishedOn, 'action' => 'official-history-source-confirmation',
                            ], JSON_THROW_ON_ERROR),
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }

                    continue;
                }

                $now = now();
                $id = DB::table('rent_period_rates')->insertGetId([
                    'period' => $period, 'rate' => $rate, 'source_url' => $sourceUrl,
                    'published_on' => $publishedOn, 'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('rent_rate_logs')->insert([
                    'rate_id' => $id,
                    'snapshot' => json_encode([
                        'period' => $period, 'rate' => $rate, 'source_url' => $sourceUrl,
                        'published_on' => $publishedOn, 'action' => 'official-history-backfill',
                    ], JSON_THROW_ON_ERROR),
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        // This migration only adds official history. Keep rows on rollback so later manual updates are not lost.
    }
};
