<?php

use App\Http\Controllers\AccueilController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FilmModalController;
use App\Http\Controllers\FilmNoteController;
use App\Http\Controllers\FilmNotesController;
use App\Http\Controllers\FilmPersonalListController;
use App\Http\Controllers\ForumApiController;
use App\Http\Controllers\ForumCommentController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\ForumDiscussionController;
use App\Http\Controllers\ListeApiController;
use App\Http\Controllers\ListeController;
use App\Http\Controllers\MembresController;
use App\Http\Controllers\MonCompteController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProposerFilmController;
use App\Http\Controllers\TmdbController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AccueilController::class, 'index'])->name('accueil');

Route::get('/liste', [ListeController::class, 'index'])->name('liste');
Route::get('/ma-liste', [ListeController::class, 'myList'])->name('ma-liste');

Route::prefix('api')->group(function () {
    Route::get('/liste/filters', [ListeApiController::class, 'filters'])->name('api.liste.filters');
    Route::get('/liste/stats', [ListeApiController::class, 'stats'])->name('api.liste.stats');
    Route::get('/liste/films', [ListeApiController::class, 'films'])->name('api.liste.films');

    Route::get('/ma-liste/filters', [ListeApiController::class, 'myFilters'])->name('api.ma-liste.filters');
    Route::get('/ma-liste/stats', [ListeApiController::class, 'myStats'])->name('api.ma-liste.stats');
    Route::get('/ma-liste/films', [ListeApiController::class, 'myFilms'])->name('api.ma-liste.films');

    Route::get('/tmdb/autofill', [TmdbController::class, 'autofill'])->name('api.tmdb.autofill');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login.show');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.show');
Route::post('/register', [AuthController::class, 'register'])->name('register.perform');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/administration', [AdminController::class, 'index'])->name('administration');
Route::get('/administration/propositions', [AdminController::class, 'propositions'])->name('administration.propositions');
Route::post('/administration/propositions/{id}/approve', [AdminController::class, 'approve'])->name('administration.propositions.approve');
Route::post('/administration/propositions/{id}/reject', [AdminController::class, 'reject'])->name('administration.propositions.reject');
Route::get('/administration/pending-films', [AdminController::class, 'pendingFilms'])->name('administration.pending-films');
Route::post('/administration/add-film', [AdminController::class, 'addFilm'])->name('administration.add-film');
Route::post('/administration/films/{id}/modify', [AdminController::class, 'modifyFilm'])->name('administration.films.modify');
Route::post('/administration/films/{id}/delete', [AdminController::class, 'deleteFilm'])->name('administration.films.delete');
Route::get('/administration/films', [AdminController::class, 'filmsList'])->name('administration.films.list');
Route::get('/administration/films/{id}', [AdminController::class, 'filmDetails'])->name('administration.films.details');
Route::get('/administration/studios', [AdminController::class, 'studiosByCategorie'])->name('administration.studios-by-categorie');
Route::get('/administration/auteurs', [AdminController::class, 'auteursByCategorie'])->name('administration.auteurs-by-categorie');
Route::get('/administration/autocomplete/studios', [AdminController::class, 'autocompleteStudios'])->name('administration.autocomplete.studios');
Route::get('/administration/autocomplete/auteurs', [AdminController::class, 'autocompleteAuteurs'])->name('administration.autocomplete.auteurs');
Route::post('/administration/sous-genres/sync', [AdminController::class, 'syncSousGenresFromTmdb'])->name('administration.sous-genres.sync');
Route::post('/administration/send-notification', [AdminController::class, 'sendNotification'])->name('administration.send-notification');
Route::post('/administration/send-email', [AdminController::class, 'sendEmail'])->name('administration.send-email');
Route::post('/administration/privacy-policy/publish', [AdminController::class, 'publishPrivacyPolicy'])->name('administration.privacy-policy.publish');
Route::match(['get', 'post'], '/administration/studio-converter', [AdminController::class, 'studioConversions'])->name('administration.studio-converter');
Route::get('/database', [AdminController::class, 'database'])->name('database');
Route::get('/forum', [ForumController::class, 'index'])->name('forum');
Route::get('/forum/categorie/{id}', [ForumDiscussionController::class, 'index'])->name('forum.category');
Route::get('/forum/discussion/{id}', [ForumDiscussionController::class, 'show'])->name('forum.discussion');
Route::post('/forum/discussion', [ForumDiscussionController::class, 'store'])->name('forum.discussion.store');
Route::post('/forum/discussion/{id}/update', [ForumDiscussionController::class, 'update'])->name('forum.discussion.update');
Route::post('/forum/discussion/{id}/delete', [ForumDiscussionController::class, 'destroy'])->name('forum.discussion.delete');
Route::post('/forum/comment', [ForumCommentController::class, 'store'])->name('forum.comment.store');
Route::post('/forum/comment/{id}/update', [ForumCommentController::class, 'update'])->name('forum.comment.update');
Route::post('/forum/comment/{id}/delete', [ForumCommentController::class, 'destroy'])->name('forum.comment.delete');
Route::post('/forum/comment/{id}/like', [ForumCommentController::class, 'toggleLike'])->name('forum.comment.like');
Route::get('/forum/api/topics', [ForumApiController::class, 'searchTopics'])->name('forum.api.topics');
Route::get('/forum/api/films', [ForumApiController::class, 'searchFilms'])->name('forum.api.films');
Route::get('/forum/api/users', [ForumApiController::class, 'searchUsers'])->name('forum.api.users');
Route::get('/membres', [MembresController::class, 'index'])->name('membres');
Route::post('/membres/title', [MembresController::class, 'updateTitle'])->name('membres.title');
Route::post('/membres/restriction', [MembresController::class, 'updateRestriction'])->name('membres.restriction');
Route::post('/membres/email', [MembresController::class, 'updateEmail'])->name('membres.email');
Route::post('/membres/username', [MembresController::class, 'updateUsername'])->name('membres.username');
Route::post('/membres/warning-reward', [MembresController::class, 'updateWarningReward'])->name('membres.warning-reward');
Route::post('/membres/promotion', [MembresController::class, 'traiterPromotion'])->name('membres.promotion');
Route::post('/membres/deletion/request', [MembresController::class, 'requestDeletion'])->name('membres.deletion.request');
Route::post('/membres/deletion/cancel', [MembresController::class, 'cancelDeletionAdmin'])->name('membres.deletion.cancel.admin');
Route::get('/membres/deletion/cancel/{token}', [MembresController::class, 'cancelDeletionByToken'])->name('membres.deletion.cancel.link');
Route::get('/mon-compte', [MonCompteController::class, 'show'])->name('mon-compte');
Route::post('/mon-compte/email', [MonCompteController::class, 'updateEmail'])->name('mon-compte.update-email');
Route::post('/mon-compte/password', [MonCompteController::class, 'updatePassword'])->name('mon-compte.update-password');
Route::post('/mon-compte/photo', [MonCompteController::class, 'uploadCroppedPhoto'])->name('mon-compte.upload-photo');
Route::post('/mon-compte/promotion', [MonCompteController::class, 'demanderPromotion'])->name('mon-compte.promotion');
Route::get('/preferences/categories-order', [MonCompteController::class, 'getCategoriesOrder'])->name('preferences.categories-order.get');
Route::post('/preferences/categories-order', [MonCompteController::class, 'saveCategoriesOrder'])->name('preferences.categories-order.save');
Route::get('/proposer-film', [ProposerFilmController::class, 'show'])->name('proposer-film.show');
Route::post('/proposer-film', [ProposerFilmController::class, 'store'])->name('proposer-film.store');
Route::get('/proposer-film/studios', [ProposerFilmController::class, 'studios'])->name('proposer-film.studios');
Route::get('/proposer-film/auteurs', [ProposerFilmController::class, 'auteurs'])->name('proposer-film.auteurs');

Route::view('/confidentialite', 'Static.confidentialite')->name('confidentialite');
Route::view('/mentions-legales', 'Static.mentions-legales')->name('mentions-legales');
Route::get('/privacy/status', [MonCompteController::class, 'privacyPolicyStatus'])->name('privacy.status');
Route::post('/privacy/ack', [MonCompteController::class, 'acknowledgePrivacyPolicy'])->name('privacy.ack');

Route::get('/forgot-password', [PasswordController::class, 'showForgot'])->name('password.forgot.show');
Route::post('/forgot-password', [PasswordController::class, 'sendForgot'])->name('password.forgot.perform');
Route::get('/reset-password/{token}', [PasswordController::class, 'showReset'])->name('password.reset.show');
Route::post('/reset-password', [PasswordController::class, 'performReset'])->name('password.reset.perform');

Route::get('/films/{film}/modal', [FilmModalController::class, 'show'])->name('films.modal');
Route::post('/films/{film}/personal-list', [FilmPersonalListController::class, 'update'])->name('films.personalList.update');
Route::post('/films/{film}/note', [FilmNoteController::class, 'update'])->name('films.note.update');
Route::get('/films/{film}/notes', [FilmNotesController::class, 'show'])->name('films.notes.show');
Route::get('/notifications/unread-count', [NotificationsController::class, 'unreadCount'])->name('notifications.unreadCount');
Route::get('/notifications', [NotificationsController::class, 'list'])->name('notifications.list');
Route::post('/notifications/{id}/read', [NotificationsController::class, 'markRead'])->name('notifications.read');
Route::delete('/notifications/{id}', [NotificationsController::class, 'delete'])->name('notifications.delete');
