<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="icon" href="{{ asset('/images/engineering_logo.svg') }}" type="image/x-icon">
    @vite('resources/css/app.css')
</head>

<body class="">
    <div class="absolute z-50 w-full shadow-lg md:shadow-none">
        <div class="flex justify-between mx-5 my-3">
            <img src="{{ asset('/images/src_logo.svg') }}" alt="Logo" class="h-12 md:h-16 xl:h-20">
            <img src="{{ asset('/images/engineering_logo.svg') }}" alt="Logo" class="h-12  md:h-16  xl:h-20">
        </div>
    </div> 
    <img class="absolute z-[-1] opacity-30 bottom-[-100px] lg:hidden" src="{{ asset('/images/src-arch.png') }}"
    alt="">
    <header class="h-screen flex justify-center lg:justify-between md:overflow-hidden">
        <div class="hidden lg:block w-[35%] relative">  
                <div
                class="absolute bg-[#A60606] w-[600px] lg:w-[700px] xl:w-[800px] 2xl:w-[860px] h-[1200px] rounded-[100px] rotate-[37deg] outline outline-[16px] outline-[#FFE366] left-[-530px] xl:left-[-400px] overflow-hidden">

                <img class="bg-[#A60606] w-[550px] xl:w-[700px] h-[650px] xl:h-[900px] object-contain absolute left-[150px] xl:left-[250px] top-[30px] rotate-[-35deg] opacity-20"
                    src="{{ asset('/images/src-arch.png') }}" alt="">
            </div>

        </div>
        <div class="flex-col w-full mx-5 content-center justify-items-center space-y-4 md:w-[60%] xl:w-[50%]">
            <div class="bg-[#A60606] rounded-[5px] px-3 text-md text-center md:py-2 sm:text-xl md:text-2xl">
                <h3 class="text-white font-bold">WELCOME TO THE SANTA ROSA</h3>
            </div>
            <h1 class="text-5xl font-black text-[#A60606] pb-4 text-center sm:text-6xl lg:text-7xl 2xl:text-8xl">
                OFFICE OF
                THE CITY
                ENGINEERING</h1>
            <a class="bg-[#A60606] text-white px-4 py-3 rounded-lg block text-center font-bold md:text-lg xl:text-2xl"
                href="/admin">Login</a>
        </div>
    </header>
</body>

</html>
