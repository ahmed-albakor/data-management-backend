<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Actor;
use App\Models\ActorsCategory;
use App\Models\Attachment;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ActorController extends Controller
{

    public function index(Request $request)
    {
        $query = Actor::with(['attachments']);

        $availableTextFields = ['name', 'expected_price', 'notes', 'phone', 'email'];

        if ($request->filled('text')) {
            $query->where(function ($q) use ($request, $availableTextFields) {
                foreach ($availableTextFields as $field) {
                    $q->orWhere($field, 'like', '%' . $request->text . '%');
                }
            });
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('age_min')) {
            $minDate = now()->subYears($request->age_min)->format('Y-m-d');
            $query->where('birthdate', '<=', $minDate);
        }
        if ($request->filled('age_max')) {
            $maxDate = now()->subYears($request->age_max)->format('Y-m-d');
            $query->where('birthdate', '>=', $maxDate);
        }

        $limit = $request->input('limit', 25);
        $limit = max(5, min($limit, 250));

        $actors = $query->paginate($limit);

        foreach ($actors as $actor) {
            if ($actor->profile_picture) {
                $actor->profile_picture = url('storage/' . $actor->profile_picture);
            }
            foreach ($actor->attachments as $attachment) {
                $attachment->file_path = url('storage/' . $attachment->file_path);
            }

            $actor->social_media = json_decode($actor->social_media, true) ?? [];
        }

        return response()->json([
            'success' => true,
            'data' => $actors->items(),
            'meta' => [
                'page' => $actors->currentPage(),
                'limit' => $actors->perPage(),
                'total' => $actors->total(),
                'last_page' => $actors->lastPage(),
            ],
        ], 200);
    }


    public function indexByCategories(Request $request)
    {
        $categories = ActorsCategory::all();

        $result = [];

        foreach ($categories as $category) {
            $query = Actor::with(['attachments'])
                ->whereRaw("FIND_IN_SET(?, categories_ids)", [$category->id]);

            $availableTextFields = ['name', 'expected_price', 'notes', 'phone', 'email'];

            if ($request->filled('text')) {
                $query->where(function ($q) use ($request, $availableTextFields) {
                    foreach ($availableTextFields as $field) {
                        $q->orWhere($field, 'like', '%' . $request->text . '%');
                    }
                });
            }

            if ($request->filled('gender')) {
                $query->where('gender', $request->gender);
            }

            if ($request->filled('age_min')) {
                $minDate = now()->subYears($request->age_min)->format('Y-m-d');
                $query->where('birthdate', '<=', $minDate);
            }
            if ($request->filled('age_max')) {
                $maxDate = now()->subYears($request->age_max)->format('Y-m-d');
                $query->where('birthdate', '>=', $maxDate);
            }

            $limit = $request->input('limit', 25);
            $limit = max(5, min($limit, 250));

            $actors = $query->paginate($limit);

            foreach ($actors as $actor) {
                if ($actor->profile_picture) {
                    $actor->profile_picture = url('storage/' . $actor->profile_picture);
                }
                foreach ($actor->attachments as $attachment) {
                    $attachment->file_path = url('storage/' . $attachment->file_path);
                }

                $actor->social_media = json_decode($actor->social_media, true) ?? [];
            }

            $result[] = [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'data' => $actors->items(),
                ],
                'meta' => [
                    'page' => $actors->currentPage(),
                    'limit' => $actors->perPage(),
                    'total' => $actors->total(),
                    'last_page' => $actors->lastPage(),
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'categories' => $result,
        ], 200);
    }



    public function show($id)
    {
        $actor = Actor::with(['attachments'])->find($id);

        if (!$actor) {
            return response()->json([
                'success' => false,
                'message' => 'الممثل غير موجود',
            ], 404);
        }

        if ($actor->profile_picture) {
            $actor->profile_picture = url('storage/' . $actor->profile_picture);
        }

        foreach ($actor->attachments as $attachment) {
            $attachment->file_path = url('storage/' . $attachment->file_path);
        }

        $actor->social_media = json_decode($actor->social_media, true) ?? [];

        return response()->json([
            'success' => true,
            'data' => $actor,
        ], 200);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:actors',
            'phone' => 'nullable|string|max:25',
            'birthdate' => 'required|date',
            'gender' => 'required|string',
            'categories_ids' => 'required|string',
            'expected_price' => 'nullable|string',
            'notes' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimetypes:image/jpeg,image/png,video/mp4,video/mpeg',
            'social_media' => 'nullable|array',
            'social_media.*.name' => 'required|string|max:255',
            'social_media.*.link' => 'required|string', // |url
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $profilePicture = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicture = ImageService::storeImage($request->file('profile_picture'), 'profile_pictures');
        }

        $socialMediaJson = json_encode($request->social_media);

        $actor = Actor::create(
            array_merge(
                $request->only('name', 'email', 'phone', 'birthdate', 'gender', 'expected_price', 'notes'),
                [
                    'profile_picture' => $profilePicture,
                    'social_media' => $socialMediaJson,
                ],
            ),
        );

        if ($request->has('attachments')) {
            foreach ($request->attachments as $attachment) {
                $type = $attachment->getMimeType();
                $storedAttachment = ImageService::storeImage($attachment, $type === 'video/mp4' ? 'videos' : 'images');
                Attachment::create([
                    'actor_id' => $actor->id,
                    'file_path' => $storedAttachment,
                    'type' => $type === 'video/mp4' ? 'video' : 'image',
                ]);
            }
        }

        $actor->profile_picture = $actor->profile_picture ? url('storage/' . $actor->profile_picture) : null;

        for ($i = 0; $i < count($actor->attachments); $i++) {
            $actor->attachments[$i]->file_path = url('storage/' . $actor->attachments[$i]->file_path);
        }

        $actor->social_media = json_decode($actor->social_media, true) ?? [];

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الممثل بنجاح',
            'data' => $actor,
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $actor = Actor::find($id);

        if (!$actor) {
            return response()->json([
                'success' => false,
                'message' => 'الممثل غير موجود',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:actors,email,' . $id,
            'phone' => 'nullable|string|max:25',
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|string',
            'categories_ids' => 'nullable|string',
            'expected_price' => 'nullable|string',
            'notes' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimetypes:image/jpeg,image/png,video/mp4,video/mpeg',
            'social_media' => 'nullable|array',
            'social_media' => 'nullable|array',
            'social_media.*.name' => 'required|string|max:255',
            'social_media.*.link' => 'required|string',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->hasFile('profile_picture')) {
            if ($actor->profile_picture) {
                $profilePicture = ImageService::updateImage($request->file('profile_picture'), 'profile_pictures', $actor->profile_picture);
            } else {
                $profilePicture = ImageService::storeImage($request->file('profile_picture'), 'profile_pictures');
            }
            $actor->profile_picture = $profilePicture;
        }

        $socialMediaJson = json_encode($request->social_media);

        $actor->update(array_merge($request->only('name', 'email', 'phone', 'birthdate', 'gender', 'expected_price', 'notes', 'categories_ids'), [
            'social_media' => $socialMediaJson,
        ]));

        if ($request->has('attachments')) {
            foreach ($request->attachments as $attachment) {
                $type = $attachment->getMimeType();
                $storedAttachment = ImageService::storeImage($attachment, $type === 'video/mp4' ? 'videos' : 'images');
                Attachment::create([
                    'actor_id' => $actor->id,
                    'file_path' => $storedAttachment,
                    'type' => $type === 'video/mp4' ? 'video' : 'image',
                ]);
            }
        }

        $actor->profile_picture = $actor->profile_picture ? url('storage/' . $actor->profile_picture) : null;

        for ($i = 0; $i < count($actor->attachments); $i++) {
            $actor->attachments[$i]->file_path = url('storage/' . $actor->attachments[$i]->file_path);
        }

        $actor->social_media = json_decode($actor->social_media, true) ?? [];

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل بيانات الممثل بنجاح',
            'data' => $actor,
        ], 200);
    }




    public function deleteAttachments(Request $request, $actor_id)
    {
        $actor = Actor::find($actor_id);
        if (!$actor) {
            return response()->json([
                'success' => false,
                'message' => 'الممثل غير موجود',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'attachment_ids' => 'required|array',
            'attachment_ids.*' => 'exists:attachments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $attachments = Attachment::whereIn('id', $request->attachment_ids)
            ->where('actor_id', $actor_id)
            ->get();

        if ($attachments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على أي مرفقات مطابقة للمعرفات المرسلة للممثل المحدد.',
            ], 404);
        }

        foreach ($attachments as $attachment) {
            Storage::delete('public/' . $attachment->file_path);
            $attachment->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المرفقات بنجاح للممثل المحدد.',
        ], 200);
    }




    public function destroy($id)
    {
        $actor = Actor::find($id);

        if (!$actor) {
            return response()->json([
                'success' => false,
                'message' => 'الممثل غير موجود',
            ], 404);
        }

        if ($actor->profile_picture) {
            Storage::delete('public/' . $actor->profile_picture);
        }

        $actor->attachments->each(function ($attachment) {
            Storage::delete('public/' . $attachment->file_path);
        });

        $actor->attachments()->delete();
        $actor->socialMediaPlatforms()->detach();
        $actor->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الممثل بنجاح',
        ], 200);
    }
}
