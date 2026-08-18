<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Seeder;

class CambodiaAdministrativeDivisionsSeeder extends Seeder
{
    /**
     * All 25 Cambodian provinces/municipalities with their districts/khans.
     *
     * Data sourced from the National Committee for Sub-National Democratic Development
     * (NCDD) and cross-referenced with ISO 3166-2:KH administrative codes.
     *
     * @return array<int, array{code: string, name_en: string, name_km: string, districts: array<int, array{code: string, name_en: string, name_km: string}>}>
     */
    private function administrativeDivisions(): array
    {
        return [
            [
                'code' => 'KH-1',
                'name_en' => 'Banteay Meanchey',
                'name_km' => 'បន្ទាយមានជ័យ',
                'districts' => [
                    ['code' => 'KH-1-01', 'name_en' => 'Mongkol Borei', 'name_km' => 'មង្គលបូរី'],
                    ['code' => 'KH-1-02', 'name_en' => 'Preah Netr Preah', 'name_km' => 'ព្រះនេត្រព្រះ'],
                    ['code' => 'KH-1-03', 'name_en' => 'Malai', 'name_km' => 'មាឡៃ'],
                    ['code' => 'KH-1-04', 'name_en' => 'Thma Puok', 'name_km' => 'ថ្មពួក'],
                    ['code' => 'KH-1-05', 'name_en' => 'Svay Chek', 'name_km' => 'ស្វាយជ្រែក'],
                    ['code' => 'KH-1-06', 'name_en' => 'Ou Chrov', 'name_km' => 'អូរជ្រៅ'],
                    ['code' => 'KH-1-07', 'name_en' => 'Sisophon', 'name_km' => 'សិរីសោភ័ណ'],
                    ['code' => 'KH-1-08', 'name_en' => 'Phnum Srok', 'name_km' => 'ភ្នំស្រុក'],
                ],
            ],
            [
                'code' => 'KH-2',
                'name_en' => 'Battambang',
                'name_km' => 'បាត់ដំបង',
                'districts' => [
                    ['code' => 'KH-2-01', 'name_en' => 'Battambang', 'name_km' => 'បាត់ដំបង'],
                    ['code' => 'KH-2-02', 'name_en' => 'Banan', 'name_km' => 'បាណន'],
                    ['code' => 'KH-2-03', 'name_en' => 'Ek Phnom', 'name_km' => 'ឯកភ្នំ'],
                    ['code' => 'KH-2-04', 'name_en' => 'Kamrieng', 'name_km' => 'កំរៀង'],
                    ['code' => 'KH-2-05', 'name_en' => 'Koas Krala', 'name_km' => 'កោះក្រឡ'],
                    ['code' => 'KH-2-06', 'name_en' => 'Maung Russei', 'name_km' => 'មោងឫស្សី'],
                    ['code' => 'KH-2-07', 'name_en' => 'Phnom Penh Commune', 'name_km' => 'ភ្នំព្រឹក'],
                    ['code' => 'KH-2-08', 'name_en' => 'Rotanak Mondol', 'name_km' => 'រតនៈម៉ុន្ដុល'],
                    ['code' => 'KH-2-09', 'name_en' => 'Samlaut', 'name_km' => 'សំឡូត'],
                    ['code' => 'KH-2-10', 'name_en' => 'Sampov Loun', 'name_km' => 'សំបួរលូន'],
                    ['code' => 'KH-2-11', 'name_en' => 'Sangkae', 'name_km' => 'សង្កែ'],
                    ['code' => 'KH-2-12', 'name_en' => 'Thma Koul', 'name_km' => 'ថ្មគោល'],
                    ['code' => 'KH-2-13', 'name_en' => 'Veal Veng', 'name_km' => 'វាលវែង'],
                ],
            ],
            [
                'code' => 'KH-3',
                'name_en' => 'Kampong Cham',
                'name_km' => 'កំពង់ចាម',
                'districts' => [
                    ['code' => 'KH-3-01', 'name_en' => 'Batheay', 'name_km' => 'បាធាយ'],
                    ['code' => 'KH-3-02', 'name_en' => 'Chamkar Leu', 'name_km' => 'ចំការលើ'],
                    ['code' => 'KH-3-03', 'name_en' => 'Cheung Prey', 'name_km' => 'ជើងព្រៃ'],
                    ['code' => 'KH-3-04', 'name_en' => 'Dambae', 'name_km' => 'ដំបែ'],
                    ['code' => 'KH-3-05', 'name_en' => 'Kampong Cham', 'name_km' => 'កំពង់ចាម'],
                    ['code' => 'KH-3-06', 'name_en' => 'Kampong Siem', 'name_km' => 'កំពង់សៀម'],
                    ['code' => 'KH-3-07', 'name_en' => 'Kang Meas', 'name_km' => 'កងមាស'],
                    ['code' => 'KH-3-08', 'name_en' => 'Koh Sotin', 'name_km' => 'កោះសូទិន'],
                    ['code' => 'KH-3-09', 'name_en' => 'Prey Chhor', 'name_km' => 'ព្រៃជរ'],
                    ['code' => 'KH-3-10', 'name_en' => 'Srei Santhor', 'name_km' => 'ស្រីសន្ធរ'],
                    ['code' => 'KH-3-11', 'name_en' => 'Stueng Trang', 'name_km' => 'ស្ទឹងត្រង់'],
                    ['code' => 'KH-3-12', 'name_en' => 'Tboung Khmum', 'name_km' => 'ត្បូងឃ្មុំ'],
                ],
            ],
            [
                'code' => 'KH-4',
                'name_en' => 'Kampong Chhnang',
                'name_km' => 'កំពង់ឆ្នាំង',
                'districts' => [
                    ['code' => 'KH-4-01', 'name_en' => 'Baribour', 'name_km' => 'បរិបូរណ៍'],
                    ['code' => 'KH-4-02', 'name_en' => 'Chol Kiri', 'name_km' => 'ឈូលគីរី'],
                    ['code' => 'KH-4-03', 'name_en' => 'Kampong Chhnang', 'name_km' => 'កំពង់ឆ្នាំង'],
                    ['code' => 'KH-4-04', 'name_en' => 'Kampong Leaeng', 'name_km' => 'កំពង់លែង'],
                    ['code' => 'KH-4-05', 'name_en' => 'Kampong Tralach', 'name_km' => 'កំពង់ត្រឡាច'],
                    ['code' => 'KH-4-06', 'name_en' => 'Rolea Pa-Ier', 'name_km' => 'រលាំងអ៊ែរ'],
                    ['code' => 'KH-4-07', 'name_en' => 'Sameakki Mean Chey', 'name_km' => 'សាមគ្គីមានជ័យ'],
                    ['code' => 'KH-4-08', 'name_en' => 'Tuek Phos', 'name_km' => 'ទឹកផុស'],
                ],
            ],
            [
                'code' => 'KH-5',
                'name_en' => 'Kampong Speu',
                'name_km' => 'កំពង់ស្ពឺ',
                'districts' => [
                    ['code' => 'KH-5-01', 'name_en' => 'Basedth', 'name_km' => 'បាសែត'],
                    ['code' => 'KH-5-02', 'name_en' => 'Chbar Mon', 'name_km' => 'ច្បារមន'],
                    ['code' => 'KH-5-03', 'name_en' => 'Kong Pisei', 'name_km' => 'កងពិសី'],
                    ['code' => 'KH-5-04', 'name_en' => 'Oral', 'name_km' => 'ឱរ៉ាល់'],
                    ['code' => 'KH-5-05', 'name_en' => 'Phnom Sruoch', 'name_km' => 'ភ្នំស្រួច'],
                    ['code' => 'KH-5-06', 'name_en' => 'Samraong Tong', 'name_km' => 'សំរោងទង'],
                    ['code' => 'KH-5-07', 'name_en' => 'Thpong', 'name_km' => 'ថ្ពង'],
                    ['code' => 'KH-5-08', 'name_en' => 'Aoral', 'name_km' => 'ឱរ៉ាល់'],
                ],
            ],
            [
                'code' => 'KH-6',
                'name_en' => 'Kampong Thom',
                'name_km' => 'កំពង់ធំ',
                'districts' => [
                    ['code' => 'KH-6-01', 'name_en' => 'Baray', 'name_km' => 'បារាយណ៍'],
                    ['code' => 'KH-6-02', 'name_en' => 'Kampong Svay', 'name_km' => 'កំពង់ស្វាយ'],
                    ['code' => 'KH-6-03', 'name_en' => 'Prasat Balangk', 'name_km' => 'ប្រាសាទបាឡង'],
                    ['code' => 'KH-6-04', 'name_en' => 'Prasat Sambour', 'name_km' => 'ប្រាសាទសំបូរ'],
                    ['code' => 'KH-6-05', 'name_en' => 'Sandan', 'name_km' => 'សន្ដាន'],
                    ['code' => 'KH-6-06', 'name_en' => 'Santuk', 'name_km' => 'សន្ទុក'],
                    ['code' => 'KH-6-07', 'name_en' => 'Stung Sen', 'name_km' => 'ស្ទឹងសែន'],
                    ['code' => 'KH-6-08', 'name_en' => 'Stoung', 'name_km' => 'ស្ទោង'],
                ],
            ],
            [
                'code' => 'KH-7',
                'name_en' => 'Kampot',
                'name_km' => 'កំពត',
                'districts' => [
                    ['code' => 'KH-7-01', 'name_en' => 'Angkor Chey', 'name_km' => 'អង្គរជ័យ'],
                    ['code' => 'KH-7-02', 'name_en' => 'Banteay Meas', 'name_km' => 'បន្ទាយមាស'],
                    ['code' => 'KH-7-03', 'name_en' => 'Chhouk', 'name_km' => 'ឈូក'],
                    ['code' => 'KH-7-04', 'name_en' => 'Dang Tong', 'name_km' => 'ដង់ទង'],
                    ['code' => 'KH-7-05', 'name_en' => 'Kampong Trach', 'name_km' => 'កំពង់ត្រាច'],
                    ['code' => 'KH-7-06', 'name_en' => 'Kampot', 'name_km' => 'កំពត'],
                    ['code' => 'KH-7-07', 'name_en' => 'Tek Chhou', 'name_km' => 'ទឹកជ្រោះ'],
                ],
            ],
            [
                'code' => 'KH-8',
                'name_en' => 'Kandal',
                'name_km' => 'កណ្ដាល',
                'districts' => [
                    ['code' => 'KH-8-01', 'name_en' => 'Angk Snuol', 'name_km' => 'អង្គស្នួល'],
                    ['code' => 'KH-8-02', 'name_en' => 'Kandal Stueng', 'name_km' => 'កណ្ដាលស្ទឹង'],
                    ['code' => 'KH-8-03', 'name_en' => 'Kien Svay', 'name_km' => 'កៀនស្វាយ'],
                    ['code' => 'KH-8-04', 'name_en' => 'Leuk Daek', 'name_km' => 'ល្វែកដែក'],
                    ['code' => 'KH-8-05', 'name_en' => 'Lvea Aem', 'name_km' => 'ល្វា​ អែម'],
                    ['code' => 'KH-8-06', 'name_en' => 'Muk Kampul', 'name_km' => 'មុខកំពូល'],
                    ['code' => 'KH-8-07', 'name_en' => 'Ponhea Leu', 'name_km' => 'ពញាឡើ'],
                    ['code' => 'KH-8-08', 'name_en' => 'Rohtasen', 'name_km' => 'រហ័សសែន'],
                    ['code' => 'KH-8-09', 'name_en' => 'Sa Ang', 'name_km' => 'សាអាង'],
                    ['code' => 'KH-8-10', 'name_en' => 'Saart', 'name_km' => 'ស្អាត'],
                    ['code' => 'KH-8-11', 'name_en' => 'Takhmau', 'name_km' => 'តាខ្មៅ'],
                    ['code' => 'KH-8-12', 'name_en' => 'Khsach Kandal', 'name_km' => 'ខ្សាច់កណ្ដាល'],
                ],
            ],
            [
                'code' => 'KH-9',
                'name_en' => 'Kep',
                'name_km' => 'កែប',
                'districts' => [
                    ['code' => 'KH-9-01', 'name_en' => 'Damnak Chang Aeur', 'name_km' => 'ដំណាក់ចង្អៀរ'],
                    ['code' => 'KH-9-02', 'name_en' => 'Kep', 'name_km' => 'កែប'],
                ],
            ],
            [
                'code' => 'KH-10',
                'name_en' => 'Koh Kong',
                'name_km' => 'កោះកុង',
                'districts' => [
                    ['code' => 'KH-10-01', 'name_en' => 'Botum Sakor', 'name_km' => 'បទុំស្ករ'],
                    ['code' => 'KH-10-02', 'name_en' => 'Kiri Sakor', 'name_km' => 'គីរីស្ករ'],
                    ['code' => 'KH-10-03', 'name_en' => 'Koh Kong', 'name_km' => 'កោះកុង'],
                    ['code' => 'KH-10-04', 'name_en' => 'Mondol Seima', 'name_km' => 'មណ្ឌលសីម'],
                    ['code' => 'KH-10-05', 'name_en' => 'Smach Mean Chey', 'name_km' => 'ស្មាច់មានជ័យ'],
                    ['code' => 'KH-10-06', 'name_en' => 'Sre Ambel', 'name_km' => 'ស្រែអំបិល'],
                    ['code' => 'KH-10-07', 'name_en' => 'Thma Bang', 'name_km' => 'ថ្មបាំង'],
                ],
            ],
            [
                'code' => 'KH-11',
                'name_en' => 'Kratié',
                'name_km' => 'ក្រចេះ',
                'districts' => [
                    ['code' => 'KH-11-01', 'name_en' => 'Chhloung', 'name_km' => 'ឈ្លូង'],
                    ['code' => 'KH-11-02', 'name_en' => 'Kratié', 'name_km' => 'ក្រចេះ'],
                    ['code' => 'KH-11-03', 'name_en' => 'Preaek Prasab', 'name_km' => 'ព្រែកប្រសព្វ'],
                    ['code' => 'KH-11-04', 'name_en' => 'Sambour', 'name_km' => 'សំបូរ'],
                    ['code' => 'KH-11-05', 'name_en' => 'Snuol', 'name_km' => 'ស្នួល'],
                ],
            ],
            [
                'code' => 'KH-12',
                'name_en' => 'Mondulkiri',
                'name_km' => 'មណ្ឌលគីរី',
                'districts' => [
                    ['code' => 'KH-12-01', 'name_en' => 'Kaoh Nheaek', 'name_km' => 'កោះញែក'],
                    ['code' => 'KH-12-02', 'name_en' => 'Keo Seima', 'name_km' => 'គែោសីម'],
                    ['code' => 'KH-12-03', 'name_en' => 'Ou Reang', 'name_km' => 'អូររាំង'],
                    ['code' => 'KH-12-04', 'name_en' => 'Pech Chreada', 'name_km' => 'ពែជ្រាដា'],
                    ['code' => 'KH-12-05', 'name_en' => 'Sen Monorom', 'name_km' => 'សែនមនោរម'],
                ],
            ],
            [
                'code' => 'KH-13',
                'name_en' => 'Oddar Meanchey',
                'name_km' => 'អូឌ្ឍរមានជ័យ',
                'districts' => [
                    ['code' => 'KH-13-01', 'name_en' => 'Anlong Veng', 'name_km' => 'អន្លង់វែង'],
                    ['code' => 'KH-13-02', 'name_en' => 'Banteay Ampil', 'name_km' => 'បន្ទាយអំពិល'],
                    ['code' => 'KH-13-03', 'name_en' => 'Chong Kal', 'name_km' => 'ជ្រោយ'],
                    ['code' => 'KH-13-04', 'name_en' => 'Samraong', 'name_km' => 'សំរោង'],
                ],
            ],
            [
                'code' => 'KH-14',
                'name_en' => 'Pailin',
                'name_km' => 'ប៉ៃលិន',
                'districts' => [
                    ['code' => 'KH-14-01', 'name_en' => 'Pailin', 'name_km' => 'ប៉ៃលិន'],
                    ['code' => 'KH-14-02', 'name_en' => 'Sala Krau', 'name_km' => 'សាលាក្រៅ'],
                ],
            ],
            [
                'code' => 'KH-15',
                'name_en' => 'Phnom Penh',
                'name_km' => 'ភ្នំពេញ',
                'districts' => [
                    ['code' => 'KH-15-01', 'name_en' => 'Chamkar Mon', 'name_km' => 'ចំការមន'],
                    ['code' => 'KH-15-02', 'name_en' => 'Doun Penh', 'name_km' => 'ដូនពេញ'],
                    ['code' => 'KH-15-03', 'name_en' => 'Prampir Meakkakra', 'name_km' => 'ប្រាំពីរមករ'],
                    ['code' => 'KH-15-04', 'name_en' => 'Tuol Kouk', 'name_km' => 'ទួលគោក'],
                    ['code' => 'KH-15-05', 'name_en' => 'Dangkao', 'name_km' => 'ដង្កោ'],
                    ['code' => 'KH-15-06', 'name_en' => 'Mean Chey', 'name_km' => 'មានជ័យ'],
                    ['code' => 'KH-15-07', 'name_en' => 'Russey Keo', 'name_km' => 'រស្សីកែវ'],
                    ['code' => 'KH-15-08', 'name_en' => 'Sen Sok', 'name_km' => 'សែនសុខ'],
                    ['code' => 'KH-15-09', 'name_en' => 'Por Sen Chey', 'name_km' => 'ពោធិ៍សែនជ័យ'],
                    ['code' => 'KH-15-10', 'name_en' => 'Chroy Changvar', 'name_km' => 'ជ្រោយចង្វារ'],
                    ['code' => 'KH-15-11', 'name_en' => 'Prek Pnov', 'name_km' => 'ព្រែកព្នៅ'],
                    ['code' => 'KH-15-12', 'name_en' => 'Chbar Ampov', 'name_km' => 'ច្បារអំពៅ'],
                    ['code' => 'KH-15-13', 'name_en' => 'Boeng Keng Kang', 'name_km' => 'បឹងកេងកង'],
                    ['code' => 'KH-15-14', 'name_en' => 'Kambol', 'name_km' => 'កំបូល'],
                ],
            ],
            [
                'code' => 'KH-16',
                'name_en' => 'Preah Sihanouk',
                'name_km' => 'ព្រះសីហនុ',
                'districts' => [
                    ['code' => 'KH-16-01', 'name_en' => 'Kampong Seila', 'name_km' => 'កំពង់សីល'],
                    ['code' => 'KH-16-02', 'name_en' => 'Krong Preah Sihanouk', 'name_km' => 'ក្រុងព្រះសីហនុ'],
                    ['code' => 'KH-16-03', 'name_en' => 'Prey Nob', 'name_km' => 'ព្រៃនប'],
                    ['code' => 'KH-16-04', 'name_en' => 'Stung Hav', 'name_km' => 'ស្ទឹងហាវ'],
                ],
            ],
            [
                'code' => 'KH-17',
                'name_en' => 'Preah Vihear',
                'name_km' => 'ព្រះវិហារ',
                'districts' => [
                    ['code' => 'KH-17-01', 'name_en' => 'Chey Saen', 'name_km' => 'ជ័យសែន'],
                    ['code' => 'KH-17-02', 'name_en' => 'Choam Ksan', 'name_km' => 'ជោមក្សាន'],
                    ['code' => 'KH-17-03', 'name_en' => 'Chhaeb', 'name_km' => 'ឆែប'],
                    ['code' => 'KH-17-04', 'name_en' => 'Kulen', 'name_km' => 'គូលែន'],
                    ['code' => 'KH-17-05', 'name_en' => 'Rovieng', 'name_km' => 'រ៉ូវៀង'],
                    ['code' => 'KH-17-06', 'name_en' => 'Sangkom Thmei', 'name_km' => 'សង្គមថ្មី'],
                    ['code' => 'KH-17-07', 'name_en' => 'Tbaeng Mean Chey', 'name_km' => 'ត្បែងមានជ័យ'],
                    ['code' => 'KH-17-08', 'name_en' => 'Varin', 'name_km' => 'វ៉ារីន'],
                ],
            ],
            [
                'code' => 'KH-18',
                'name_en' => 'Prey Veng',
                'name_km' => 'ព្រៃវែង',
                'districts' => [
                    ['code' => 'KH-18-01', 'name_en' => 'Ba Phnum', 'name_km' => 'បាភ្នំ'],
                    ['code' => 'KH-18-02', 'name_en' => 'Kamchay Mear', 'name_km' => 'កំចាយម៉ារ'],
                    ['code' => 'KH-18-03', 'name_en' => 'Kampong Leaev', 'name_km' => 'កំពង់លែវ'],
                    ['code' => 'KH-18-04', 'name_en' => 'Kanhchriech', 'name_km' => 'កញ្ច្រៀច'],
                    ['code' => 'KH-18-05', 'name_en' => 'Me Sang', 'name_km' => 'មេសាង'],
                    ['code' => 'KH-18-06', 'name_en' => 'Mesang', 'name_km' => 'ម៉ែសាង'],
                    ['code' => 'KH-18-07', 'name_en' => 'Peam Chor', 'name_km' => 'ពាមជរ'],
                    ['code' => 'KH-18-08', 'name_en' => 'Peam Ro', 'name_km' => 'ពាមរ៉ូ'],
                    ['code' => 'KH-18-09', 'name_en' => 'Preah Sdach', 'name_km' => 'ព្រះស្ដេច'],
                    ['code' => 'KH-18-10', 'name_en' => 'Prey Veng', 'name_km' => 'ព្រៃវែង'],
                    ['code' => 'KH-18-11', 'name_en' => 'Pung Ror', 'name_km' => 'ពង្រ'],
                    ['code' => 'KH-18-12', 'name_en' => 'Sithor Kandal', 'name_km' => 'ស៊ីថរ​ ​កណ្ដាល'],
                ],
            ],
            [
                'code' => 'KH-19',
                'name_en' => 'Pursat',
                'name_km' => 'ពោធិ៍សាត់',
                'districts' => [
                    ['code' => 'KH-19-01', 'name_en' => 'Bakan', 'name_km' => 'បាកាន'],
                    ['code' => 'KH-19-02', 'name_en' => 'Kandieng', 'name_km' => 'កន្ទៀង'],
                    ['code' => 'KH-19-03', 'name_en' => 'Krakor', 'name_km' => 'ក្រគរ'],
                    ['code' => 'KH-19-04', 'name_en' => 'Phnum Kravanh', 'name_km' => 'ភ្នំក្រវាញ'],
                    ['code' => 'KH-19-05', 'name_en' => 'Pursat', 'name_km' => 'ពោធិ៍សាត់'],
                    ['code' => 'KH-19-06', 'name_en' => 'Veal Veng', 'name_km' => 'វាលវែង'],
                ],
            ],
            [
                'code' => 'KH-20',
                'name_en' => 'Ratanakiri',
                'name_km' => 'រតនគីរី',
                'districts' => [
                    ['code' => 'KH-20-01', 'name_en' => 'Andong Meas', 'name_km' => 'អណ្ដូងម្អាស'],
                    ['code' => 'KH-20-02', 'name_en' => 'Ban Lung', 'name_km' => 'បានលុង'],
                    ['code' => 'KH-20-03', 'name_en' => 'Bar Kaev', 'name_km' => 'បាកែវ'],
                    ['code' => 'KH-20-04', 'name_en' => 'Koun Mom', 'name_km' => 'គូនម៉ំ'],
                    ['code' => 'KH-20-05', 'name_en' => 'Lumphat', 'name_km' => 'លំផាត'],
                    ['code' => 'KH-20-06', 'name_en' => 'Ou Chum', 'name_km' => 'អូរជំ'],
                    ['code' => 'KH-20-07', 'name_en' => 'Ou Ya Dav', 'name_km' => 'អូរយ៉ាដាវ'],
                    ['code' => 'KH-20-08', 'name_en' => 'Ta Veng', 'name_km' => 'តាវែង'],
                    ['code' => 'KH-20-09', 'name_en' => 'Veun Sai', 'name_km' => 'វ៍ើនស្អៃ'],
                ],
            ],
            [
                'code' => 'KH-21',
                'name_en' => 'Siem Reap',
                'name_km' => 'សៀមរាប',
                'districts' => [
                    ['code' => 'KH-21-01', 'name_en' => 'Angkor Chum', 'name_km' => 'អង្គរជំ'],
                    ['code' => 'KH-21-02', 'name_en' => 'Angkor Thom', 'name_km' => 'អង្គរធំ'],
                    ['code' => 'KH-21-03', 'name_en' => 'Banteay Srei', 'name_km' => 'បន្ទាយស្រី'],
                    ['code' => 'KH-21-04', 'name_en' => 'Chi Kraeng', 'name_km' => 'ជីក្រែង'],
                    ['code' => 'KH-21-05', 'name_en' => 'Kralanh', 'name_km' => 'ក្រឡាញ'],
                    ['code' => 'KH-21-06', 'name_en' => 'Prasat Bakong', 'name_km' => 'ប្រាសាទបាគង'],
                    ['code' => 'KH-21-07', 'name_en' => 'Puok', 'name_km' => 'ពោក'],
                    ['code' => 'KH-21-08', 'name_en' => 'Sampov Meas', 'name_km' => 'សំបួរមាស'],
                    ['code' => 'KH-21-09', 'name_en' => 'Siem Reap', 'name_km' => 'សៀមរាប'],
                    ['code' => 'KH-21-10', 'name_en' => 'Soutr Nikom', 'name_km' => 'ស្ទ្រើននីគំ'],
                    ['code' => 'KH-21-11', 'name_en' => 'Srey Snom', 'name_km' => 'ស្រីស្នំ'],
                    ['code' => 'KH-21-12', 'name_en' => 'Svay Leu', 'name_km' => 'ស្វាយលើ'],
                    ['code' => 'KH-21-13', 'name_en' => 'Varin', 'name_km' => 'វ៉ារីន'],
                    ['code' => 'KH-21-14', 'name_en' => 'Chreav', 'name_km' => 'ជ្រាវ'],
                ],
            ],
            [
                'code' => 'KH-22',
                'name_en' => 'Stung Treng',
                'name_km' => 'ស្ទឹងត្រែង',
                'districts' => [
                    ['code' => 'KH-22-01', 'name_en' => 'Sesan', 'name_km' => 'សេសាន'],
                    ['code' => 'KH-22-02', 'name_en' => 'Siem Bouk', 'name_km' => 'សៀមបូក'],
                    ['code' => 'KH-22-03', 'name_en' => 'Stung Treng', 'name_km' => 'ស្ទឹងត្រែង'],
                    ['code' => 'KH-22-04', 'name_en' => 'Thala Barivat', 'name_km' => 'ថាឡាបរិវ៉ាត'],
                    ['code' => 'KH-22-05', 'name_en' => 'Ou Svay', 'name_km' => 'អូស្វាយ'],
                ],
            ],
            [
                'code' => 'KH-23',
                'name_en' => 'Svay Rieng',
                'name_km' => 'ស្វាយរៀង',
                'districts' => [
                    ['code' => 'KH-23-01', 'name_en' => 'Chantrea', 'name_km' => 'ចន្ទ្រា'],
                    ['code' => 'KH-23-02', 'name_en' => 'Kampong Rou', 'name_km' => 'កំពង់រោ'],
                    ['code' => 'KH-23-03', 'name_en' => 'Romeas Haek', 'name_km' => 'រំាស​ហែក'],
                    ['code' => 'KH-23-04', 'name_en' => 'Svay Chrum', 'name_km' => 'ស្វាយជ្រំ'],
                    ['code' => 'KH-23-05', 'name_en' => 'Svay Rieng', 'name_km' => 'ស្វាយរៀង'],
                    ['code' => 'KH-23-06', 'name_en' => 'Svay Teab', 'name_km' => 'ស្វាយទាប'],
                ],
            ],
            [
                'code' => 'KH-24',
                'name_en' => 'Takéo',
                'name_km' => 'តាកែវ',
                'districts' => [
                    ['code' => 'KH-24-01', 'name_en' => 'Angkor Borei', 'name_km' => 'អង្គរបូរី'],
                    ['code' => 'KH-24-02', 'name_en' => 'Bati', 'name_km' => 'បាទី'],
                    ['code' => 'KH-24-03', 'name_en' => 'Borei Cholsar', 'name_km' => 'បូរីចូលស'],
                    ['code' => 'KH-24-04', 'name_en' => 'Daun Keo', 'name_km' => 'ដូនកែវ'],
                    ['code' => 'KH-24-05', 'name_en' => 'Kaoh Andaet', 'name_km' => 'កោះអណ្ដែត'],
                    ['code' => 'KH-24-06', 'name_en' => 'Kiri Vong', 'name_km' => 'គីរីវ៉ង'],
                    ['code' => 'KH-24-07', 'name_en' => 'Prey Kabbas', 'name_km' => 'ព្រៃកប្បាស'],
                    ['code' => 'KH-24-08', 'name_en' => 'Samraong', 'name_km' => 'សំរោង'],
                    ['code' => 'KH-24-09', 'name_en' => 'Treang', 'name_km' => 'ត្រែង'],
                    ['code' => 'KH-24-10', 'name_en' => 'Tram Kak', 'name_km' => 'ត្រាំក្អក'],
                ],
            ],
            [
                'code' => 'KH-25',
                'name_en' => 'Tboung Khmum',
                'name_km' => 'ត្បូងឃ្មុំ',
                'districts' => [
                    ['code' => 'KH-25-01', 'name_en' => 'Krouch Chhmar', 'name_km' => 'ក្រូចឆ្មារ'],
                    ['code' => 'KH-25-02', 'name_en' => 'Memot', 'name_km' => 'មេម៉ត'],
                    ['code' => 'KH-25-03', 'name_en' => 'Ponhea Kraek', 'name_km' => 'ពញាក្រែក'],
                    ['code' => 'KH-25-04', 'name_en' => 'Suong', 'name_km' => 'សួង'],
                    ['code' => 'KH-25-05', 'name_en' => 'Tboung Khmum', 'name_km' => 'ត្បូងឃ្មុំ'],
                    ['code' => 'KH-25-06', 'name_en' => 'Dambae', 'name_km' => 'ដំបែ'],
                ],
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->administrativeDivisions() as $division) {
            $province = Province::firstOrCreate(
                ['code' => $division['code']],
                [
                    'name_en' => $division['name_en'],
                    'name_km' => $division['name_km'],
                    'is_active' => true,
                ],
            );

            foreach ($division['districts'] as $districtData) {
                District::firstOrCreate(
                    ['code' => $districtData['code']],
                    [
                        'province_id' => $province->id,
                        'name_en' => $districtData['name_en'],
                        'name_km' => $districtData['name_km'],
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
