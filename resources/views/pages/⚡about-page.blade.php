<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div>
    <div class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            {{-- Page Title --}}
            <div class="text-center mb-16">
                <p class="text-blue-600 font-semibold text-sm uppercase tracking-widest mb-2">បង្កើតឡើងឆ្នាំ ២០២៤ · Since 2024</p>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl font-khmer mb-4">
                    អំពីហាង ផាន់ណា កុំព្យូទ័រ
                </h1>
                <p class="text-lg text-slate-500 max-w-2xl mx-auto">
                    Your trusted technology partner in Phnom Penh — premium computers, expert repairs, and honest advice.
                </p>
            </div>

            {{-- Our Story --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center mb-24">
                <div class="relative group">
                    <div class="absolute -inset-1 bg-gradient-to-br from-blue-600 to-cyan-500 rounded-3xl opacity-20 group-hover:opacity-30 blur transition duration-500"></div>
                    <div class="relative rounded-2xl overflow-hidden shadow-2xl aspect-[4/3]">
                        <img src="{{ asset('images/phannacomputer.png') }}" alt="Phanna Computer Storefront"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-900/60 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-6">
                            <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm border border-white/20 rounded-full px-4 py-2">
                                <svg class="w-4 h-4 text-blue-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-white text-sm font-khmer">ភ្នំពេញ · Phnom Penh</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <p class="text-blue-600 font-semibold text-sm uppercase tracking-widest mb-2">Our Story · ប្រវត្តិរបស់យើង</p>
                        <h2 class="text-3xl font-bold text-slate-900 font-khmer leading-tight">
                            បច្ចេកវិទ្យាទំនើប<br>
                            <span class="text-blue-600">សម្រាប់រាល់គ្នា</span>
                        </h2>
                    </div>

                    <p class="text-slate-600 leading-relaxed font-khmer text-base">
                        ហាង ​ផាន់ណា កុំព្យូទ័រ គឺជាហាងឯកជនមួយឈានមុខគេ ដែលមានលក់កុំព្យូទ័រថ្មី
                        និងកុំព្យូទ័រមួយទឹកដែលមានគុណភាពខ្ពស់ ព្រមទាំងមានសេវាកម្មជួសជុលកុំព្យូទ័រគ្រប់ប្រភេទនៅកម្ពុជា។
                    </p>

                    <p class="text-slate-600 leading-relaxed font-khmer text-base">
                        ហាងរបស់យើងត្រូវបានបង្កើតឡើងនៅឆ្នាំ២០២៤ ក្នុងគោលបំណងផ្តល់ជូននូវផលិតផលបច្ចេកវិទ្យាទំនើបៗ
                        និងសេវាកម្មបច្ចេកទេសប្រកបដោយទំនុកចិត្ត ជូនដល់សិស្ស និស្សិត និងអ្នកធ្វើការការិយាល័យ។
                    </p>

                    {{-- Stats row --}}
                    <div class="grid grid-cols-3 gap-4 py-4">
                        <div class="text-center p-4 bg-white rounded-2xl shadow-sm border border-slate-100">
                            <p class="text-2xl font-bold text-blue-600">2024</p>
                            <p class="text-xs text-slate-500 mt-1 font-khmer">ឆ្នាំបង្កើត</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-2xl shadow-sm border border-slate-100">
                            <p class="text-2xl font-bold text-blue-600">100%</p>
                            <p class="text-xs text-slate-500 mt-1 font-khmer">ទំនុកចិត្ត</p>
                        </div>
                        <div class="text-center p-4 bg-white rounded-2xl shadow-sm border border-slate-100">
                            <p class="text-2xl font-bold text-blue-600">24/7</p>
                            <p class="text-xs text-slate-500 mt-1">Support</p>
                        </div>
                    </div>

                    {{-- Location badge --}}
                    <div class="flex items-start gap-4 bg-blue-600 rounded-2xl p-5 text-white">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm text-blue-100 mb-1">ទីតាំងរបស់យើង · Our Location</p>
                            <p class="font-khmer text-white leading-relaxed">
                                ផ្ទះលេខ ១១៤ ផ្លូវលេខ ១៣៨ ក្រោយសាលារៀនសន្ធរមុខ រាជធានីភ្នំពេញ។
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- What We Offer --}}
            <div class="mb-24">
                <div class="text-center mb-14">
                    <p class="text-blue-600 font-semibold text-sm uppercase tracking-widest mb-2">What We Offer</p>
                    <h2 class="text-3xl font-bold text-slate-900 font-khmer">អ្វីដែលយើងផ្តល់ជូន</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Card 1 --}}
                    <div class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-blue-100 transition-colors duration-300"></div>
                        <div class="relative">
                            <div class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg shadow-blue-200">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mb-4">
                                @foreach(['Dell', 'MSI', 'ASUS', 'HP'] as $brand)
                                    <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full font-medium">{{ $brand }}</span>
                                @endforeach
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 font-khmer mb-3">កុំព្យូទ័រថ្មី និងមួយទឹក</h3>
                            <p class="text-slate-500 font-khmer text-sm leading-relaxed">
                                យើងមានលក់កុំព្យូទ័រម៉ាកល្បីៗ ថ្មីប្រអប់ និងមួយទឹកស្អាតៗ ដែលមានការធានាត្រឹមត្រូវ និងគុណភាពខ្ពស់។
                            </p>
                        </div>
                    </div>

                    {{-- Card 2 (featured) --}}
                    <div class="group relative bg-gradient-to-br from-blue-600 to-blue-700 rounded-3xl p-8 shadow-xl shadow-blue-200 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/2 -translate-x-1/2"></div>
                        <div class="relative">
                            <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="inline-flex items-center gap-1.5 bg-white/20 rounded-full px-3 py-1 mb-4">
                                <span class="text-white/90 text-xs font-medium">Hardware & Software</span>
                            </div>
                            <h3 class="text-lg font-bold text-white font-khmer mb-3">សេវាកម្មជួសជុល</h3>
                            <p class="text-blue-100 font-khmer text-sm leading-relaxed">
                                ទទួលជួសជុលកុំព្យូទ័រគ្រប់ប្រភេទ ដោយជាងមានបទពិសោធន៍ ធានាភាពរហ័ស និងទុកចិត្តបាន។
                            </p>
                        </div>
                    </div>

                    {{-- Card 3 --}}
                    <div class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-100 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-emerald-100 transition-colors duration-300"></div>
                        <div class="relative">
                            <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300 shadow-lg shadow-emerald-200">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div class="flex flex-wrap gap-1.5 mb-4">
                                <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-medium border border-emerald-100">Trusted</span>
                                <span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-medium border border-emerald-100">Honest</span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 font-khmer mb-3">ទំនុកចិត្ត និងទំនួលខុសត្រូវ</h3>
                            <p class="text-slate-500 font-khmer text-sm leading-relaxed">
                                យើងផ្តល់ការប្រឹក្សាដោយស្មោះត្រង់ ជួយជ្រើសរើសកុំព្យូទ័រដែលស័ក្តិសមបំផុតទៅនឹងតម្រូវការ និងថវិការបស់អ្នក។
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Map Section --}}
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-8 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-slate-900 font-khmer">ស្វែងរកយើងនៅលើផែនទី</h2>
                            <p class="text-sm text-slate-500">Find Us on the Map</p>
                        </div>
                    </div>
                </div>
                <div class="w-full h-[450px]">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d977.2017101495586!2d104.89959066962274!3d11.565700713164123!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310951e1db3e5a69%3A0x3aa2d474226aa070!2sPhanna%20Computer%20Shop!5e0!3m2!1sen!2skh!4v1772776109867!5m2!1sen!2skh"
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

        </div>
    </div>
</div>