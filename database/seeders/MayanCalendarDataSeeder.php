<?php

namespace Database\Seeders;

use App\Models\MayanCalendarData;
use Illuminate\Database\Seeder;

class MayanCalendarDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $haabMonths = [
            ['Pop', 'Waterlily Mat'], ['Wo\'', 'Frog Croaking'], ['Sip', 'Red Conjunction'], ['Sotz\'', 'Bat'],
            ['Sek', 'Fright'], ['Xul', 'Dog'], ['Yaxk\'in', 'New Sun'], ['Mol', 'Water'],
            ['Ch\'en', 'Black Storm'], ['Yax', 'Green Storm'], ['Sak\'', 'White Storm'], ['Keh', 'Red Storm'],
            ['Mak', 'Enclosed'], ['K\'ank\'in', 'Yellow Sun'], ['Muwan\'', 'Owl'], ['Pax', 'Planting Time'],
            ['K\'ayab', 'Turtle'], ['Kumk\'u', 'Granary'], ['Wayeb\'', 'Five Unlucky Days'],
        ];

        $tzolkinDays = [
            ['Imix', 'Waterlily'], ['Ik\'', 'Wind'], ['Ak\'bal', 'Night-House'], ['K\'an', 'Maize'],
            ['Chikchan', 'Snake'], ['Kimi', 'Death'], ['Manik\'', 'Deer'], ['Lamat', 'Rabbit/Star'],
            ['Muluk', 'Water'], ['Ok', 'Dog'], ['Chuwen', 'Monkey'], ['Eb\'', 'Road'],
            ['Ben', 'Reed'], ['Ix', 'Jaguar'], ['Men', 'Eagle'], ['Kib\'', 'Wax/Earth'],
            ['Kaban', 'Earthquake'], ['Etz\'nab', 'Flint'], ['Kawak', 'Storm'], ['Ajaw', 'Lord/Ruler'],
        ];

        foreach ($haabMonths as $index => $month) {
            MayanCalendarData::create([
                'calendar_type' => 'haab',
                'position' => $index,
                'name_maya' => $month[0],
                'name_english' => $month[1],
            ]);
        }

        foreach ($tzolkinDays as $index => $day) {
            MayanCalendarData::create([
                'calendar_type' => 'tzolkin',
                'position' => $index,
                'name_maya' => $day[0],
                'name_english' => $day[1],
            ]);
        }
    }
}
