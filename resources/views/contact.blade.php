<x-site-layout title="Contact">
    <section class="mx-auto grid max-w-6xl gap-12 px-4 pt-8 sm:px-6 sm:pt-16 md:grid-cols-2">
        <div>
            <p class="text-xl font-medium text-forest">Get in touch</p>
            <h1 class="mt-2 font-heading text-4xl font-bold sm:text-5xl">Contact <span class="font-accent italic text-forest">us</span></h1>
            <p class="mt-6 text-lg text-gray-700">
                Questions about the pilot, reviewer access or partner locations? Reach the PhytoQuest team through the details below.
            </p>

            <dl class="mt-10 space-y-6">
                <div>
                    <dt class="text-sm font-semibold uppercase tracking-wider text-forest">Address</dt>
                    <dd class="mt-1 text-gray-800">José Rizal University, 80 Shaw Blvd., Mandaluyong City, Metro Manila</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold uppercase tracking-wider text-forest">Phone</dt>
                    <dd class="mt-1 text-gray-800">+63 123-456-7890</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold uppercase tracking-wider text-forest">Email</dt>
                    <dd class="mt-1 text-gray-800">hello@phytoquest.test</dd>
                </div>
            </dl>
        </div>

        <div class="card bg-sand p-8">
            <h2 class="font-heading text-2xl font-semibold">Send a message</h2>
            <form action="mailto:hello@phytoquest.test" method="GET" enctype="text/plain" class="mt-6 space-y-5">
                <div>
                    <x-input-label for="subject" value="Subject" />
                    <x-text-input id="subject" name="subject" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="body" value="Message" />
                    <textarea id="body" name="body" rows="5" required class="mt-1 block w-full rounded-lg border-forest/20 bg-sand/60 shadow-sm focus:border-forest focus:ring-leaf"></textarea>
                </div>
                <x-primary-button>Open in email app</x-primary-button>
            </form>
        </div>
    </section>
</x-site-layout>
