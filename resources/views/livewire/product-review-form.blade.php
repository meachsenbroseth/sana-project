<div>
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-6">
        <h3 class="mb-4 text-lg font-semibold text-gray-900">Write a review</h3>

        @if (session()->has('review_success'))
            <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                {{ session('review_success') }}
            </div>
        @endif

        @error('review')
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        <form wire:submit="submitReview" class="space-y-4">
            <div>
                <label for="review-rating" class="mb-1 block text-sm font-medium text-gray-700">Rating</label>
                <select
                    id="review-rating"
                    wire:model="rating"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                    <option value="5">5 - Excellent</option>
                    <option value="4">4 - Very Good</option>
                    <option value="3">3 - Good</option>
                    <option value="2">2 - Fair</option>
                    <option value="1">1 - Poor</option>
                </select>
                @error('rating')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="review-title" class="mb-1 block text-sm font-medium text-gray-700">Title</label>
                <input
                    id="review-title"
                    type="text"
                    wire:model="title"
                    placeholder="Short summary (optional)"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                >
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="review-comment" class="mb-1 block text-sm font-medium text-gray-700">Review</label>
                <textarea
                    id="review-comment"
                    wire:model="comment"
                    rows="4"
                    placeholder="Share your experience (optional)"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                ></textarea>
                @error('comment')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="submitReview"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Submit review
                </button>
            </div>
        </form>
    </div>
</div>
