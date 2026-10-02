<x-site-layout title="About">
    <section class="mx-auto max-w-6xl px-6 pt-16">
        <p class="text-xl font-medium text-forest">About the system</p>
        <h1 class="mt-2 font-heading text-5xl font-bold">PhytoQuest <span class="font-accent italic text-forest">in the field</span></h1>
        <p class="mt-6 max-w-3xl text-lg text-gray-700">
            PhytoQuest is a responsive web application that supports the early fieldwork stage of botanical and pharmaceutical research.
            It joins plant search, GPS-confirmed sightings and source-backed plant profiles into a single research tool for pharmacy
            researchers, botanists, biomedical engineers, students and qualified reviewers.
        </p>
    </section>

    <section class="mx-auto mt-16 grid max-w-6xl gap-6 px-6 md:grid-cols-3">
        @foreach ([
            ['Search & profiles', 'Look up plants by common or scientific name and read habitat, toxin, extract and reference records, each with a source label.'],
            ['Field sightings', 'Log a sighting with coordinates, date, photo, plant part and location status. Records stay pending until expert review.'],
            ['Protected locations', 'Rare, protected or privately located plants are shown only as generalized areas to protect people and populations.'],
        ] as [$heading, $body])
            <div class="card bg-sand">
                <h2 class="font-heading text-xl font-semibold text-forest">{{ $heading }}</h2>
                <p class="mt-3 text-gray-700">{{ $body }}</p>
            </div>
        @endforeach
    </section>

    <section class="mx-auto mt-16 grid max-w-6xl gap-10 px-6 md:grid-cols-2">
        <div>
            <h2 class="font-heading text-3xl font-semibold">Safety boundaries</h2>
            <ul class="mt-5 list-disc space-y-2 ps-5 text-gray-700">
                <li>No diagnosis, treatment, dosage, preparation or consumption advice.</li>
                <li>No chemical measurement or toxin quantification from photographs.</li>
                <li>Traditional use, lab, animal and human evidence are never presented as equivalent.</li>
                <li>Sensitive coordinates are never published without review.</li>
            </ul>
        </div>
        <div>
            <h2 class="font-heading text-3xl font-semibold">The team</h2>
            <p class="mt-5 text-gray-700">José Rizal University, ITC C301-301I Integrative Programming and Technologies 1, A.Y. 2026-2027.</p>
            <ul class="mt-4 space-y-1 text-gray-800">
                <li><span class="font-semibold">Leader:</span> Francesca Isabel Dival</li>
                <li>Trisha Nicole Aguilar</li>
                <li>Matt Mondares</li>
                <li>Keith Alan Speirs</li>
            </ul>
        </div>
    </section>
</x-site-layout>
