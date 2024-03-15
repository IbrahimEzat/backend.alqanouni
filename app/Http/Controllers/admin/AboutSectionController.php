<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AboutSection;
use App\Traits\GeneralTrait;
use Illuminate\Http\Request;

use function PHPUnit\Framework\returnSelf;

class AboutSectionController extends Controller
{
    use GeneralTrait;

    public function store(Request $request)
    {
        $data = AboutSection::where('section_name', $request->section_name)->first();
        if($data) {
            $data->update([
                'about_section' => $request->about_section
            ]);
        } else {
            $data = AboutSection::create([
                'section_name' => $request->section_name,
                'about_section' => $request->about_section
            ]);
        }
        return $this->mainResponse(true, 'تم إضافة النبذة بنجاح', []);
    }

    public function getAbout(Request $request)
    {
        $data = AboutSection::where('section_name', $request->section_name)->first();
        if($data)
            return $this->mainResponse(true, 'هذه النبذة التي طلبتها', $data);

        return $this->mainResponse(false, 'حدث خطأ ما', []);
    }

    public function getAboutAll()
    {
        $data = AboutSection::all()->groupBy('section_name');
        if($data)
            return $this->mainResponse(true, 'هذه النبذة التي طلبتها', $data);

        return $this->mainResponse(false, 'حدث خطأ ما', []);
    }

}
