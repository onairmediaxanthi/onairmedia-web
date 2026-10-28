<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-full bg-onair px-7 py-3.5 font-semibold text-white hover:bg-onair-dark disabled:opacity-60']) }}>
    {{ $slot }}
</button>
