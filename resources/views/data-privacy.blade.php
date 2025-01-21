<div x-data class="flex">
    <x-filament::modal width="xl" id="custom-modal-handle" display-classes="block" class="!overflow-y-visible">
        <div class="relative">
            <x-slot name="header">
                <span class="text-xl font-bold">Privacy policy</span>
            </x-slot>

            <!-- Content wrapper -->
            <div class="relative overflow-y-auto" style="max-height: calc(100vh - 200px);">
                <div class="p-4">
                    Privacy Policy Effective Date: [Insert Date] This privacy policy outlines the data collection, use, and protection practices for [Your System Name], a web application designed for internal use within [Department Name]. We are committed to safeguarding the privacy of the data you provide when using our services. By using this system, you agree to the collection and use of information in accordance with this policy. 1. Information We Collect We collect the following types of data for the purposes of providing our services: Personal Information: When you create a user account, we collect your name and email address. Document Data: We extract data from documents using tools like the SMLAOTPDF parser and Tesseract OCR. This may include personal, business, or other sensitive information contained within the documents. Activity Logs: We track and log your activity within the system, including the pages you access, searches you perform, and actions you take within the Privacy Policy Effective Date: [Insert Date] This privacy policy outlines the data collection, use, and protection practices for [Your System Name], a web application designed for internal use within [Department Name]. We are committed to safeguarding the privacy of the data you provide when using our services. By using this system, you agree to the collection and use of information in accordance with this policy. 1. Information We Collect We collect the following types of data for the purposes of providing our services: Personal Information: When you create a user account, we collect your name and email address. Document Data: We extract data from documents using tools like the SMLAOTPDF parser and Tesseract OCR. This may include personal, business, or other sensitive information contained within the documents. Activity Logs: We track and log your activity within the system, including the pages you access, searches you perform, and actions you take within the Privacy Policy Effective Date: [Insert Date] This privacy policy outlines the data collection, use, and protection practices for [Your System Name], a web application designed for internal use within [Department Name]. We are committed to safeguarding the privacy of the data you provide when using our services. By using this system, you agree to the collection and use of information in accordance with this policy. 1. Information We Collect We collect the following types of data for the purposes of providing our services: Personal Information: When you create a user account, we collect your name and email address. Document Data: We extract data from documents using tools like the SMLAOTPDF parser and Tesseract OCR. This may include personal, business, or other sensitive information contained within the documents. Activity Logs: We track and log your activity within the system, including the pages you access, searches you perform, and actions you take within the
                </div>
            </div>

            <div class="sticky bottom-0 bg-white p-4 border-t">
                <div class="flex justify-end">
                    <x-filament::button 
                        type="button" 
                        x-on:click="$dispatch('close-modal', { id: 'custom-modal-handle' })"
                    >
                        I understand
                    </x-filament::button>
                </div>
            </div>
        </div>
    </x-filament::modal>

    <x-heroicon-s-information-circle 
        class="w-6 h-6 text-gray-400 hover:text-gray-500 cursor-pointer"
        title="Privacy policy"
        x-on:click="$dispatch('open-modal', { id: 'custom-modal-handle' })"
    />
</div>