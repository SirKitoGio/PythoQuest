<footer class="mt-24 bg-forest text-cream">
    <div class="mx-auto grid max-w-6xl gap-12 px-6 py-14 md:grid-cols-[auto_1fr_auto] md:gap-10">
        <x-application-logo class="h-24 w-24 rounded-full ring-4 ring-leaf/40" />

        <div>
            <p class="font-heading text-3xl font-semibold">PhytoQuest</p>
            <p class="mt-2 max-w-sm text-cream/80">A location-aware botanical research support system for students and field researchers.</p>

            <p class="mt-8 text-sm font-medium tracking-widest">CONTACT US</p>
            <ul class="mt-3 space-y-3 text-sm text-cream/90">
                <li class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-leaf" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-6.2-7-11a7 7 0 1114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    José Rizal University, 80 Shaw Blvd., Mandaluyong City
                </li>
                <li class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-leaf" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2l2 5-2.5 1.5a11 11 0 006 6L14 13l5 2v2a2 2 0 01-2 2A16 16 0 013 5z"/></svg>
                    +63 123-456-7890
                </li>
                <li class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-leaf" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16v12H4z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 7l8 6 8-6"/></svg>
                    hello@phytoquest.test
                </li>
            </ul>
        </div>

        <div>
            <p class="font-heading text-lg font-semibold tracking-wide">QUICK LINKS</p>
            <ul class="mt-4 space-y-3 text-cream/90">
                <li><a href="{{ route('home') }}" class="hover:text-leaf">Home</a></li>
                <li><a href="{{ route('sightings.create') }}" class="hover:text-leaf">Plant Scanner</a></li>
                <li><a href="{{ route('home') }}#suggestions" class="hover:text-leaf">Today's Plant Suggestions</a></li>
                <li><a href="{{ route('home') }}#categories" class="hover:text-leaf">Categories</a></li>
                <li><a href="{{ route('home') }}#recent" class="hover:text-leaf">Recently Explored</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-cream/15 py-5 text-center text-sm text-cream/70">
        <p>For research and education only. PhytoQuest does not give medical, dosage or consumption advice.</p>
        <p class="mt-1">&copy; 2026 PhytoQuest. All rights reserved.</p>
    </div>
</footer>
