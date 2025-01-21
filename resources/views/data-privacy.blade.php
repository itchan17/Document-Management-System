<div x-data class="flex">
    <x-filament::modal width="2xl" id="custom-modal-handle" display-classes="block" class="!overflow-y-visible">
        <div class="relative">
            <x-slot name="header">
                <h1 class="text-xl font-bold">Privacy policy</h1>
            </x-slot>

            <!-- Content wrapper -->
            <div class="relative overflow-y-auto" style="max-height: calc(100vh - 200px);">
                <div class="space-y-2">
                    <div class="space-y-2">
                    <p>
                            This statement of privacy describes the procedures for gathering, using, and safeguarding data for the Document Management System, a web application created for the City Engineering Office's internal usage. We are dedicated to protecting the confidentiality of the information you submit when using our services.
                        </p>

                        <p>
                            By using this system, you consent to information being gathered and used in line with this policy.
                        </p>
                    </div>
                    <div class="space-y-2">
                        <h1 class="text-md font-bold">1. Information We Collect</h1>

                        <p>We collect the following types of data for the purposes of providing our services:</p>
                                   
                        <ul class="list-disc list-inside px-6">
                            <li><strong>Personal Information:</strong> We collect your name and email address for creating an account.</li>
                            <li><strong>Document Data:</strong> We extract data from documents using tools like the Smalot PDF parser and Tesseract OCR. This may include personal, business, or other sensitive information contained within the documents.</li>
                            <li><strong>Activity Logs:</strong> We track and log your activity within the system, including the documents you create, update, delete, and access.</li>
                        </ul>    
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-md font-bold">2. How We Use the Information</h1>
                        
                        <p>We use the data we collect for the following purposes:</p>
                                   
                        <ul class="list-disc list-inside px-6">
                            <li><strong>Extracting Data:</strong> Using Smalot PDF parser and Tesseract OCR to extract text and data from uploaded PDFs and images.</li>
                            <li><strong>Document Indexing:</strong> Using MeiliSearch to index and facilitate the search of documents and data within the system.</li>
                            <li><strong>User Management:</strong> To create and manage user accounts, and provide access to the system.</li>
                            <li><strong>Activity Tracking:</strong> To monitor and track the actions of users within the system, ensuring security.</li>
                        </ul>    
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-md font-bold">3. Data Storage and Protection</h1>
                        
                        <p>We take appropriate measures to safeguard the data we collect, including:</p>
                                   
                        <ul class="list-disc list-inside px-6">
                            <li><strong>Role-Based Access Control:</strong> To guarantee that only authorized people can read or modify particular information, access to sensitive data will be limited according to user roles.</li>
                            <li><strong>MeiliSearch:</strong> MeiliSearch indexes document data for quick and efficient searches. Access to the indexed data is restricted through API keys and permissions.</li>
                            <li><strong>OCR and Data Parsing:</strong> Data extracted using Tesseract OCR and the Smalot PDF parser is stored within the system to make it easier to search with MeiliSearch. Access to this data is limited to authorized individuals and internal processes only, and it is never shared with outside parties.</li>
                        </ul>    
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-md font-bold">4. Third-Party Services</h1>
                        
                        <p>We may use the following third-party tools to process data:</p>
                                   
                        <ul class="list-disc list-inside px-6">
                            <li><strong>Smalot PDF Parser:</strong> Used to extract data from PDF documents.</li>
                            <li><strong>Tesseract OCR:</strong> Used to extract data from images.</li>
                            <li><strong>MeiliSearch:</strong> A third-party search engine used to index documents for quick retrieval.</li>
                        </ul>    
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-md font-bold">5. User Rights</h1>
                        
                        <p>As a user of our system, you have the following rights:</p>
                                   
                        <ul class="list-disc list-inside px-6">
                            <li><strong>Access to Information:</strong> You can access the information we store about you, including your name, email, and activity logs.</li>
                            <li><strong>Correction of Data:</strong> You can update or correct any inaccurate or incomplete information within the system.</li>
                            <li><strong>Deletion of Data:</strong> You can delete your account and any associated data within the system.</li>
                        </ul>    
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-md font-bold">6. Data Retention</h1>
                        
                        <p>We only keep your personal information for as long as needed to provide the services. Activity logs will be kept for auditing purposes, and when data is no longer needed, it will be deleted.</p>                                                     
                    </div>

                    <div class="space-y-2">
                        <h1 class="text-md font-bold">7. Security of Your Information</h1>
                        
                        <p>We take the following precautions to safeguard your information in a reasonable manner:</p>
                                   
                        <ul class="list-disc list-inside px-6">
                            <li><strong>Access Control:</strong> Only authorized users have access to the information stored within the system. Access is limited according to permissions and roles.</li>
                        </ul>    
                    </div>
                </div>               
            </div>

            <div class="pt-6">
                <div class="flex justify-end ">
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