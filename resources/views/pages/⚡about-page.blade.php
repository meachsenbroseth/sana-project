<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="bg-gray-50 py-16 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

<div class="text-center max-w-3xl mx-auto mb-16">
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl font-khmer mb-4">
                អំពីហាង ផាន់ណា កុំព្យូទ័រ
            </h1>
            <p class="text-lg text-gray-600">
                Your trusted technology partner in Phnom Penh for premium products and expert repairs.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-stretch mb-20">
            <div class="relative rounded-2xl overflow-hidden shadow-xl h-full min-h-[400px] lg:min-h-0">
                <img src="{{ asset('images/phannacomputer.png') }}" alt="Phanna Computer Storefront"
                    class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-blue-900/40 to-transparent"></div>
            </div>

            <div class="flex flex-col justify-center space-y-6 py-4">
                <h2 class="text-2xl font-bold text-gray-900 font-khmer">ប្រវត្តិរបស់យើង (Our Story)</h2>

                <p class="text-gray-700 leading-relaxed font-khmer text-lg">
                    ហាង ​ផាន់ណា កុំព្យូទ័រ គឺជាហាងឯកជនមួយឈានមុខគេ ដែលមានលក់កុំព្យូទ័រថ្មី
                    និងកុំព្យូទ័រមួយទឹកដែលមានគុណភាពខ្ពស់ ព្រមទាំងមានសេវាកម្មជួសជុលកុំព្យូទ័រគ្រប់ប្រភេទនៅកម្ពុជា។
                </p>

                <p class="text-gray-700 leading-relaxed font-khmer text-lg">
                    ហាងរបស់យើងត្រូវបានបង្កើតឡើងនៅឆ្នាំ២០២៤ ក្នុងគោលបំណងផ្តល់ជូននូវផលិតផលបច្ចេកវិទ្យាទំនើបៗ
                    និងសេវាកម្មបច្ចេកទេសប្រកបដោយទំនុកចិត្តជូនដល់សិស្ស និស្សិត និងអ្នកធ្វើការការិយាល័យ។
                    ដោយសារការគាំទ្រពីអតិថិជន យើងតែងតែយកចិត្តទុកដាក់លើគុណភាព តម្លៃសមរម្យ
                    និងសេវាកម្មក្រោយពេលលក់ដ៏ល្អឥតខ្ចោះ។
                </p>

                <div class="bg-blue-50 border-l-4 border-blue-600 p-5 rounded-r-lg mt-6">
                    <h3 class="text-blue-800 font-semibold mb-2 font-khmer">ទីតាំងរបស់យើង (Our Location)</h3>
                    <p class="text-blue-900 font-khmer">
                        ផ្ទះលេខ ១១៤ ផ្លូវលេខ ១៣៨ ក្រោយសាលារៀនសន្ធរមុខ រាជធានីភ្នំពេញ។
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-16">
            <h2 class="text-2xl font-bold text-center text-gray-900 font-khmer mb-12">អ្វីដែលយើងផ្តល់ជូន (What We Offer)
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                    <div
                        class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mb-6 group-hover:bg-blue-600 group-hover:text-white transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 font-khmer mb-3">កុំព្យូទ័រថ្មី និងមួយទឹក</h3>
                    <p class="text-gray-600 font-khmer">
                        យើងមានលក់កុំព្យូទ័រម៉ាកល្បីៗ (Dell, MSI, ASUS, HP...) ថ្មីប្រអប់ និងមួយទឹកស្អាតៗ
                        ដែលមានការធានាត្រឹមត្រូវ និងគុណភាពខ្ពស់។
                    </p>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                    <div
                        class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mb-6 group-hover:bg-blue-600 group-hover:text-white transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                            </path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 font-khmer mb-3">សេវាកម្មជួសជុល</h3>
                    <p class="text-gray-600 font-khmer">
                        ទទួលជួសជុលកុំព្យូទ័រគ្រប់ប្រភេទ (Hardware & Software) ដោយជាងមានបទពិសោធន៍ច្បាស់លាស់ ធានាភាពរហ័ស
                        និងទុកចិត្តបាន។
                    </p>
                </div>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition group">
                    <div
                        class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mb-6 group-hover:bg-blue-600 group-hover:text-white transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 font-khmer mb-3">ទំនុកចិត្ត និងទំនួលខុសត្រូវ</h3>
                    <p class="text-gray-600 font-khmer">
                        យើងផ្តល់ជូនការប្រឹក្សាយោបល់ដោយស្មោះត្រង់
                        ដើម្បីជួយលោកអ្នកជ្រើសរើសកុំព្យូទ័រដែលស័ក្តិសមបំផុតទៅនឹងតម្រូវការ និងថវិការបស់អ្នក។
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-20">
            <h2 class="text-2xl font-bold text-center text-gray-900 font-khmer mb-8">ស្វែងរកយើងនៅលើផែនទី (Find Us on the
                Map)</h2>
            <div class="bg-white p-3 rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="w-full h-[400px] md:h-[500px] rounded-2xl overflow-hidden relative">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d977.2017101495586!2d104.89959066962274!3d11.565700713164123!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x310951e1db3e5a69%3A0x3aa2d474226aa070!2sPhanna%20Computer%20Shop!5e0!3m2!1sen!2skh!4v1772776109867!5m2!1sen!2skh"
                        width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade" class="absolute top-0 left-0 w-full h-full">
                    </iframe>
                </div>
            </div>
        </div>

    </div>
</div>
