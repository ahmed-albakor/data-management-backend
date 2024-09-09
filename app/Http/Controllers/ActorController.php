<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Actor;
use App\Models\Attachment;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ActorController extends Controller
{

    public function index(Request $request)
    {
        $query = Actor::with(['attachments', 'socialMediaPlatforms']);

        $availableTextFields = ['name', 'address', 'notes', 'phone'];

        if ($request->filled('text')) {
            $query->where(function ($q) use ($request, $availableTextFields) {
                foreach ($availableTextFields as $field) {
                    $q->orWhere($field, 'like', '%' . $request->text . '%');
                }
            });
        }

        if ($request->filled('age_min')) {
            $minDate = now()->subYears($request->age_min)->format('Y-m-d');
            $query->where('birthdate', '<=', $minDate);
        }
        if ($request->filled('age_max')) {
            $maxDate = now()->subYears($request->age_max)->format('Y-m-d');
            $query->where('birthdate', '>=', $maxDate);
        }

        if ($request->filled('social_media_platform')) {
            $query->whereHas('socialMediaPlatforms', function ($q) use ($request) {
                $q->where('name', $request->social_media_platform);
            });
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


    public function show($id)
    {
        $actor = Actor::with(['attachments', 'socialMediaPlatforms'])->find($id);

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
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimetypes:image/jpeg,image/png,video/mp4,video/mpeg',
            'social_media' => 'nullable|array',
            'social_media.*.platform_id' => 'required|exists:social_media_platforms,id',
            'social_media.*.link' => 'required|string',
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

        $actor = Actor::create(array_merge($request->only('name', 'email', 'phone', 'birthdate', 'gender', 'address', 'notes'), [
            'profile_picture' => $profilePicture
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

        if ($request->has('social_media')) {
            foreach ($request->social_media as $platform) {
                $actor->socialMediaPlatforms()->attach($platform['platform_id'], ['link' => $platform['link']]);
            }
        }

        $actor->profile_picture = $actor->profile_picture ? url('storage/' . $actor->profile_picture) : null;

        for ($i = 0; $i < count($actor->attachments); $i++) {
            $actor->attachments[$i]->file_path = url('storage/' .  $actor->attachments[$i]->file_path);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الممثل بنجاح',
            'data' => $actor->load('socialMediaPlatforms'),
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
            'address' => 'nullable|string',
            'notes' => 'nullable|string',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimetypes:image/jpeg,image/png,video/mp4,video/mpeg',
            'social_media' => 'nullable|array',
            'social_media.*.platform_id' => 'required|exists:social_media_platforms,id',
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

        $actor->update($request->only('name', 'email', 'phone', 'birthdate', 'gender', 'address', 'notes'));

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

        if ($request->has('social_media')) {
            $actor->socialMediaPlatforms()->detach();
            foreach ($request->social_media as $platform) {
                $actor->socialMediaPlatforms()->attach($platform['platform_id'], ['link' => $platform['link']]);
            }
        }

        $actor->profile_picture = $actor->profile_picture ? url('storage/' . $actor->profile_picture) : null;

        for ($i = 0; $i < count($actor->attachments); $i++) {
            $actor->attachments[$i]->file_path = url('storage/' .  $actor->attachments[$i]->file_path);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل بيانات الممثل بنجاح',
            'data' => $actor->load('socialMediaPlatforms'),
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
