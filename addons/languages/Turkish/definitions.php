<?php
// Copyright 2013 Toby Zerner, Simon Zerner, Translated by Cagatay ONCUL
// This file is part of esoTalk. Please see the included license file for usage information.

ET::$languageInfo["Turkish"] = array(
	"locale" => "tr-TR",
	"name" => "Turkish",
	"description" => "Turkish language pack.",
	"version" => ESOTALK_VERSION,
	"author" => "Cagatay ONCUL",
	"authorEmail" => "cagatayoncul@esotalk.org",
	"authorURL" => "http://github.com/cagatayoncul",
	"license" => "GPLv2"
);

// Define the character set that this language uses.
$definitions["charset"] = "utf-8";

$definitions["date.full"] = "d M Y, H:i \G\M\TO"; // see http://au.php.net/manual/en/function.date.php for details

$definitions["%d day ago"] = "dün";
$definitions["%d days ago"] = "%d gün önce";
$definitions["%d hour ago"] = "1 saat önce";
$definitions["%d hours ago"] = "%d saat önce";
$definitions["%d minute ago"] = "1 dakika önce";
$definitions["%d minutes ago"] = "%d dakika önce";
$definitions["%d month ago"] = "1 ay önce";
$definitions["%d months ago"] = "%d ay önce";
$definitions["%d second ago"] = "1 saniye önce";
$definitions["%d seconds ago"] = "%d saniye önce";
$definitions["%d week ago"] = "geçen hafta";
$definitions["%d weeks ago"] = "%d hafta önce";
$definitions["%d year ago"] = "geçen yıl";
$definitions["%d years ago"] = "%d yıl önce";

$definitions["%s and %s"] = "%s ve %s";
$definitions["%s can view this conversation."] = "%s bu tartışmayı görüntüleyebilir";
$definitions["%s changed %s's group to %s."] = "%s changed %s's group to %s."; //TODO: translate
$definitions["%s changed your group to %s."] = "%s changed your group to %s."; //TODO: translate
$definitions["%s conversation"] = "%s tartışması";
$definitions["%s conversations"] = "%s tartışmaları";
$definitions["%s invited you to %s."] = "%s sizi %s a davet etti.";
$definitions["%s joined the forum."] = "%s foruma katıldı.";
$definitions["%s post"] = "%s gönderi";
$definitions["%s posted %s"] = "%s, %s gönderdi";
$definitions["%s posted in %s."] = "%s posted in %s.";  //TODO: translate
$definitions["%s posts"] = "%s gönderiler";
$definitions["%s reply"] = "%s yanıt";
$definitions["%s replies"] = "%s yanıt";
$definitions["%s Settings"] = "%s Ayarlar";
$definitions["%s started the conversation %s."] = "%s, %s tartışmasını başlattı.";
$definitions["%s tagged you in a post."] = "%s sizden bahsetti.";
$definitions["%s will be able to view this conversation."] = "%s will be able to view this conversation.";  //TODO: translate
$definitions["%s will be able to:"] = "%s will be able to:";

$definitions["Success!"] = "Başarılı!";
$definitions["A new version of esoTalk (%s) is available."] = "esoTalk (%s), yeni bir versionu mevcut.";
$definitions["a private conversation"] = "özel tartışma";
$definitions["Access the administrator control panel."] = "Kontrol paneline erişim.";
$definitions["Account type"] = "Hesap tipi";
$definitions["Activate"] = "Aktif";
$definitions["Activity"] = "Hareketler";
$definitions["Add"] = "Ekle";
$definitions["Administration"] = "Yönetim";
$definitions["Administrator email"] = "Yönetici e-postası";
$definitions["Administrator password"] = "Yönetici şifresi";
$definitions["Administrator username"] = "Yönetici kullanıcı adı";
$definitions["Advanced options"] = "Gelişmiş seçenekler";
$definitions["All Channels"] = "Tüm kanallar";
$definitions["Allow members to edit their own posts:"] = "Kullanıcıların kendi gönderilerini düzenlemelerine izin ver:";
$definitions["Already have an account? <a href='%s' class='link-login'>Log in!</a>"] = "Hesabınız var mı? <a href='%s' class='link-login'>Giriş Yap!</a>";
$definitions["Appearance"] = "Görünüm";
$definitions["Automatically star conversations that I reply to"] = "Cevap yazdığım tartışmaları takip et";
$definitions["Avatar"] = "Avatar";

$definitions["Back to channels"] = "Kanallara geri dön";
$definitions["Back to conversation"] = "Tartışmaya geri dön";
$definitions["Back to member"] = "Üyeye geri dön";
$definitions["Back to members"] = "Üyelere geri dön";
$definitions["Back to search"] = "Aramaya geri dön";
$definitions["Background color"] = "Arkaplan rengi";
$definitions["Background image"] = "Arkaplan resmi";
$definitions["Base URL"] = "Site URL";  //TODO: translate
$definitions["Bold"] = "Kalın";
$definitions["By %s"] = "By %s";  //TODO: translate

$definitions["Can suspend/unsuspend members"] = "Üyeleri aktif/pasifleştirebilir.";
$definitions["Cancel"] = "İptal";
$definitions["Change %s's Permissions"] = "%s'ın yetkilerini değiştir.";
$definitions["Change avatar"] = "Avatarı değiştir";
$definitions["Change Channel"] = "Kanalı Değiştir";
$definitions["Change channel"] = "Kanalı değiştir";
$definitions["Change username"] = "Kullanıcı adını değiştir";
$definitions["Change Password or Email"] = "E-posta veya Şifreyi Değiştir";
$definitions["Change Password"] = "Şifreyi Değiştir";
$definitions["Change password"] = "Şifreyi değiştir";
$definitions["Change permissions"] = "İzinleri değiştir";
$definitions["Change"] = "Değiştir";
$definitions["Channel description"] = "Açıklamayı değiştir";
$definitions["Channel List"] = "Kanal listesi";
$definitions["Channel title"] = "Kanal başlığı";
$definitions["Channel slug"] = "Kanal slug"; //TODO: translate
$definitions["Channels"] = "Kanallar";
$definitions["Choose a secure password of at least %s characters"] = "%s karakterlerini içerek güvenli bir şifre belirleyin.";
$definitions["Choose what people will see when they first visit your forum."] = "İnsanları forumunuza girdiğinde ilk neyi göreceğini belirleyin";
$definitions["Click on a member's name to remove them."] = "Silmek için üye adına tıklayın.";
$definitions["Close registration"] = "Üyeliği kapat";
$definitions["Confirm password"] = "Şifre doğrulama";
$definitions["Context"] = "Kaynak";
$definitions["Controls"] = "Kontroller";
$definitions["Conversation"] = "Tartışma";
$definitions["Conversations participated in"] = "Conversations participated in"; //TODO : translate
$definitions["Conversations started"] = "Tartışma başlatıldı";
$definitions["Conversations"] = "Tartışmalar";
$definitions["Copy permissions from"] = "İzinleri buradan kopyala";
$definitions["Create Channel"] = "Kalan oluştur";
$definitions["Create Group"] = "Grup oluştur";
$definitions["Create Member"] = "Üye oluştur";
$definitions["Customize how users can become members of your forum."] = "Kullanıcıların foruma nasıl üye olacağını düzenle.";
$definitions["Customize your forum's appearance"] = "Forum görünümü düzenle";

$definitions["Dashboard"] = "Gösterge paneli";
$definitions["Default forum language"] = "Varsıyalın forum lisani(dili)";
$definitions["<strong>Delete</strong> all conversations forever."] = "Tüm tartışmaları tamamiyle <strong>Sil</strong>.";
$definitions["Delete Channel"] = "Kanalı sil";
$definitions["Delete conversation"] = "Tartışmayı sil";
$definitions["Delete member"] = "Üyeyi sil";
$definitions["Delete Member"] = "Üyeyi sil";
$definitions["<strong>Delete this member's posts.</strong> All of this member's posts will be marked as deleted, but will be able to be restored manually."] = "<strong>Bu kullanıcının gönderileri sil.</strong> Bu kullanıcının tüm gönderileri SİLİNDİ olarak işaretlenecektir. İleride tekrar geriye dönderilebilir.";
$definitions["Delete"] = "Sil";
$definitions["Deleted %s by %s"] = "%s, %s tarafından silindi";
$definitions["Disable"] = "Devredışı bırak";
$definitions["Discard"] = "İptal et";
$definitions["Don't have an account? <a href='%s' class='link-join'>Sign up!</a>"] = "Henüz bir hesabınız yok mu? <a href='%s' class='link-join'>Kayıt ol!</a>";
$definitions["Don't repeat"] = "Tekrarlama"; //TODO : translate

$definitions["Edit Channel"] = "Kanalı Düzenle";
$definitions["Edit Group"] = "Grubu Düzenle";
$definitions["Edit member groups"] = "Üyenin gruplarını düzenle";
$definitions["Edit your profile"] = "Profilimi düzenle";
$definitions["Edit"] = "Düzenle";
$definitions["Edited %s by %s"] = "%s, %s tarafından düzeltildi.";
$definitions["Editing permissions"] = "Düzenleme izinleri";
$definitions["Email me when I'm added to a private conversation"] = "Özel bir tartışmaya eklendiğimde beni E-posta ile bilgilendir";
$definitions["Email me when someone posts in a conversation I have starred"] = "Takip ettiğim tartışmaya gönderide bulunulduğunda beni E-posta ile bilgilendir";
$definitions["Email"] = "E-posta";
$definitions["Enable"] = "Aktifleştir";
$definitions["Enabled"] = "Aktifleştirilidi";
$definitions["Enter a conversation title"] = "Bir başlık giriniz";
$definitions["Error"] = "Hata";
$definitions["esoTalk version"] = "esoTalk version";
$definitions["Everyone"] = "Herkes";

$definitions["Fatal Error"] = "Uh oh! Ölümcül bir hatayla karşılaşıldı...";
$definitions["Feed"] = "Feed";
$definitions["Filter by name or group..."] = "İsim veya gruba göre filtrele...";
$definitions["Filter conversations..."] = "Tartışmaları filtrele...";
$definitions["Find this post"] = "Bu gönderiyi bul";  //TODO : translate
$definitions["First posted"] = "İlk gönderilen";
$definitions["Follow to receive notifications"] = "Bildirimleri almak için izle";
$definitions["For %s seconds"] = "%s saniye içinde"; //TODO : translate
$definitions["Forever"] = "Her zaman";
$definitions["Forgot?"] = "Unuttum?";
$definitions["Forgot Password"] = "Şifremi Unuttum";
$definitions["Forum header"] = "Forum başlığı";  //TODO : translate
$definitions["Forum language"] = "Forum dili";
$definitions["Forum Settings"] = "Forum Settings";
$definitions["Forum Statistics"] = "Forum İstatistikleri";
$definitions["Forum title"] = "Forum başlığı";
$definitions["forumDescription"] = "%s is a web-forum discussing %s, and %s.";  //TODO : translate

$definitions["Give this group the 'moderate' permission on all existing channels"] = "Bu gruba tüm kanallarda 'modaratör' izinlerini ver";
$definitions["Global permissions"] = "Genel izinler";
$definitions["Go to top"] = "Yukarı git";
$definitions["Group name"] = "Grup adı";
$definitions["group.administrator"] = "Yönetici";
$definitions["group.administrator.plural"] = "Yöneticiler";
$definitions["group.guest"] = "Misafir";
$definitions["group.guest.plural"] = "Misafirler";
$definitions["group.member"] = "Üye";
$definitions["group.member.plural"] = "Üyeler";
$definitions["group.Moderator"] = "Moderatör";
$definitions["group.Moderator.plural"] = "Moderatörler";
$definitions["group.suspended"] = "Durduruldu";
$definitions["Groups can be used to categorize members and give them certain privileges."] = "Gruplar üyeleri kategorize etmek ve onları ayrıcalık vermek için kullanılır.";
$definitions["Groups"] = "Gruplar";

$definitions["Header"] = "Başlık";
$definitions["Header color"] = "Başlık rengi";
$definitions["Hide"] = "Gizle";
$definitions["Home page"] = "Ana sayfa";
$definitions["HTML is allowed."] = "HTML 'e izin verildi.";

$definitions["If you run into any other problems or just want some help with the installation, feel free to ask for assistance at the <a href='%s'>esoTalk support forum</a>."] = "Eğer farklı bir hatayla karşılaşırsanız, destek asistanlarımıza sorularınızı sormakta çekinmeyin. <a href='%s'>esoTalk destek forumu</a>.";
$definitions["Install esoTalk"] = "esoTalk Yükle";
$definitions["Install My Forum"] = "Forumumu Yükle";
$definitions["Installed Languages"] = "Yüklü dil paketleri";
$definitions["Installed Plugins"] = "Yüklü Eklentiler";
$definitions["Installed plugins"] = "Yüklü eklentiler";
$definitions["Installed Skins"] = "Yüklü Temalar";
$definitions["Installed skins"] = "Yüklü temalar";
$definitions["is %s"] = "is %s"; //TODO : translate

$definitions["Joined"] = "Katıldı";
$definitions["Jump to last"] = "Sonuncuya git";
$definitions["Jump to unread"] = "Okunmayana git";
$definitions["just now"] = "az önce";

$definitions["Keep me logged in"] = "Beni hatırla";
$definitions["<strong>Keep this member's posts.</strong> All of this member's posts will remain intact, but will show [deleted] as the author."] = "<strong>Bu üyenin mesajlarını tut.</strong> Bu üyenin mesajları silinmez ancak, yazar tarafından [silinmiş] olarak gözükecektir.";

$definitions["label.draft"] = "Taslak";
$definitions["label.locked"] = "Kilitle";
$definitions["label.muted"] = "Susturulmuş";
$definitions["label.private"] = "Özel";
$definitions["label.sticky"] = "Yapışkan";
$definitions["Labels"] = "Etiketler";
$definitions["Last active"] = "Son etkinlik";
$definitions["Last active %s"] = "Son etkin %s"; //TODO : translate
$definitions["Latest News"] = "Yeni eklenenler";
$definitions["Loading..."] = "Yükleniyor...";
$definitions["Lock"] = "Kilitle";
$definitions["Log In"] = "Giriş yap";
$definitions["Log Out"] = "Çıkış yap";

$definitions["Make member and online list visible to:"] = "Make member and online list visible to:"; //TODO : translate
$definitions["Manage Groups"] = "Grupları Yönet";
$definitions["Manage Languages"] = "Dil Paketlerini Yönet";
$definitions["Manage your forum's channels (categories)"] = "Forum kanallarını (kategorilerini) yönet";
$definitions["Mark as read"] = "Okundu olarak işaretle";
$definitions["Mark all as read"] = "Tümünü okundu olarak işaretle";
$definitions["Mark listed as read"] = "Listelenenleri okundu olarak işaretle";
$definitions["Maximum size of %s. %s."] = "Maksimum boyut %s. %s."; //TODO : translate
$definitions["Member groups"] = "Üye grupları";
$definitions["Member list"] = "Üye listesi";
$definitions["Member List"] = "Üye Listesi";
$definitions["Member privacy"] = "Üye gizliliği";
$definitions["Members Allowed to View this Conversation"] = "Üyelerin bu tartışmayı görüntülemesine izin ver";
$definitions["Members Online"] = "Üyeler çevrimiçi";
$definitions["Members"] = "Üyeler";
$definitions["Mobile skin"] = "Mobil teması";
$definitions["Moderate"] = "Yönet";  //TODO : translate
$definitions["<strong>Move</strong> conversations to the following channel:"] = "Tartışmaları belirtilen kanala <strong>Taşı</strong>:";
$definitions["Mute conversation"] = "Tartışmayı sustur";
$definitions["MySQL database"] = "MySQL veritabanı";
$definitions["MySQL host address"] = "MySQL sunucu adresi";
$definitions["MySQL password"] = "MySQL şifresi";
$definitions["MySQL queries"] = "MySQL sorgular";
$definitions["MySQL table prefix"] = "MySQL tablo ön eki";
$definitions["MySQL username"] = "MySQL kullanıcı adı";
$definitions["MySQL version"] = "MySQL version";

$definitions["Name"] = "İsim";
$definitions["never"] = "asla";
$definitions["%s new"] = "%s yeni";
$definitions["New conversation"] = "Yeni tartışma";
$definitions["New Conversation"] = "Yeni Tartışma";
$definitions["New conversations in the past week"] = "Son haftadaki yeni tartışmalar";
$definitions["New email"] = "Yeni eposta";
$definitions["New members in the past week"] = "Son haftadaki yeni kullanıcılar";
$definitions["New password"] = "Yeni şifre";
$definitions["New posts in the past week"] = "Son haftadaki yeni gönderiler";
$definitions["New username"] = "Yeni kullanıcı adı";
$definitions["Next Step"] = "Sonraki adım";
$definitions["Next"] = "İleri";
$definitions["No preview"] = "Önizleme yok";
$definitions["No"] = "Hayır";
$definitions["Notifications"] = "Bildirimler";
$definitions["Now"] = "Şimdi";

$definitions["OK"] = "TAMAM";
$definitions["Online"] = "Çevrimiçi";
$definitions["online"] = "çevrimiçi";
$definitions["Open registration"] = "Kayıt aç";
$definitions["optional"] = "isteğe bağlı";
$definitions["Order By:"] = "Sırala:";
$definitions["Original Post"] = "Orjinal Gönderi";

$definitions["Page Not Found"] = "Sayfa Bulunamadı";
$definitions["Password"] = "Şifre";
$definitions["PHP version"] = "PHP version";
$definitions["Plugins"] = "Eklentiler";
$definitions["Post a Reply"] = "Cevap Gönder";
$definitions["Post count"] = "Gönderi adeti";
$definitions["Posts"] = "Gönderiler";
$definitions["Preview"] = "Önizleme";
$definitions["Previous"] = "Önceki";

$definitions["Quote"] = "Alıntı";
$definitions["quote"] = "alıntı";

$definitions["Read more"] = "Devamını oku";
$definitions["Recent posts"] = "Yeni gönderiler";
$definitions["Recover Password"] = "Şifre Kurtar";
$definitions["Registered members"] = "Kayıtlı üyeler";
$definitions["Registration"] = "Üyelik";
$definitions["Remove avatar"] = "Avatarı sil";
$definitions["Rename Member"] = "Üyeyi Yeniden Adlandır";
$definitions["Reply"] = "Cevapla";
$definitions["Report a bug"] = "Bir hata bildir";
$definitions["Require users to confirm their email address"] = "Üyelerin eposta adresini doğrulaması gerekir";
$definitions["Restore"] = "Geri yükle";
$definitions["restore"] = "geri yükle";
$definitions["Reset"] = "Reset";

$definitions["Save Changes"] = "Kaydet";
$definitions["Save Draft"] = "Taslak Olarak Kaydet";
$definitions["Search conversations..."] = "Tartışma ara...";
$definitions["Search within this conversation..."] = "Tartışma içerisinde ara...";
$definitions["Search"] = "Ara";
$definitions["See the private conversations I've had with %s"] = "%s ile olan özel tartışmalarımı gör";
$definitions["Set a New Password"] = "Yeni şifre belirle";
$definitions["Settings"] = "Ayarlar";
$definitions["Show an image in the header"] = "Başlıkta bir resim göster";
$definitions["Show matching posts"] = "Eşleşen gönderileri göster";
$definitions["Show the channel list by default"] = "Varsayılan kanal listesi göster";
$definitions["Show the conversation list by default"] = "Varsayılan tartışma listesini göster";
$definitions["Show the forum title in the header"] = "Forum başlığını üst başlıkta göster";
$definitions["Sign Up"] = "Kayıt Ol";
$definitions["Skins"] = "Temalar";
$definitions["Specify Setup Information"] = "Kurulum Bilgileri";
$definitions["Star to receive notifications"] = "Bildirimleri almak için takip et";
$definitions["Starred"] = "Takip ediliyor";
$definitions["Start"] = "Başlat";
$definitions["Start a conversation"] = "Bir tartışma başlat";
$definitions["Start a new conversation"] = "Yeni bir tartışma başlat";
$definitions["Start a private conversation with %s"] = "%s ile birlikte özel bir tartışma başlat";
$definitions["Start Conversation"] = "Tartışmayı Başlat";
$definitions["Starting a conversation"] = "Tartışma başlatılıyor";
$definitions["Statistics"] = "İstatistikler";
$definitions["statistic.conversation.plural"] = "%s tartışmalar";
$definitions["statistic.conversation"] = "%s tartışma";
$definitions["statistic.member.plural"] = "%s üyeler";
$definitions["statistic.member"] = "%s üye";
$definitions["statistic.online.plural"] = "%s çevrimiçi";
$definitions["statistic.online"] = "%s çevrimçi";
$definitions["statistic.post.plural"] = "%s gönderiler";
$definitions["statistic.post"] = "%s gönderi";
$definitions["Sticky"] = "Yapışkan";
$definitions["Subscribe"] = "Abone ol";
$definitions["Subscribed"] = "Abone olundu";
$definitions["Subscription"] = "Abonelik";
$definitions["Suspend member"] = "Kullanıcıyı pasifleştir";
$definitions["Suspend members."] = "Kullanıcıları pasifleştir.";
$definitions["Suspend"] = "Pasifleştir";

$definitions["To get started with your forum, you might like to:"] = "To get started with your forum, you might like to:"; //TODO : translate

$definitions["Unhide"] = "Göster";
$definitions["Uninstall"] = "Kaldır";
$definitions["Unlock"] = "Kiliti kaldır";
$definitions["Unmute conversation"] = "Susturmayı kaldır";
$definitions["Unstarred"] = "Takip etmiyor";
$definitions["Unsticky"] = "Yapışkan değil";
$definitions["Unsubscribe new users by default"] = "Varsayılan olarak yeni kullanıcıları abone yapma";
$definitions["Unsubscribe"] = "Abonelikten çıkar";
$definitions["Unsubscribed"] = "Abonelikten çıkarıldı";
$definitions["Unsuspend member"] = "Üyeyi aktifleştir";
$definitions["Unsuspend"] = "Aktifleştir";
$definitions["Until someone replies"] = "Bir yanıt yazılıncaya kadar";
$definitions["Untitled conversation"] = "Başlıksız tartışma";
$definitions["Upgrade esoTalk"] = "esoTalk u yükselt";
$definitions["Use a background image"] = "Bir arka plan resmi kullan";
$definitions["Use for mobile"] = "Mobil için kullan";
$definitions["Use friendly URLs"] = "SEO uyumlu URL ler kullan";
$definitions["Used to verify your account and subscribe to conversations"] = "Hesabınızı doğrulamak ve konuşmaları abone olmak için kullanılır";
$definitions["Username"] = "Kullanıcı adı";
$definitions["Username or Email"] = "Kullanıcı adı yada E-posta";

$definitions["View %s's profile"] = "%s Profilini Göster";
$definitions["View all notifications"] = "Tüm bildirimleri göster";
$definitions["View more"] = "Daha fazla";
$definitions["View your profile"] = "Profilimi göster";
$definitions["View"] = "Göster";
$definitions["Viewing: %s"] = "Gösterilen: %s";
$definitions["viewingPosts"] = "<b>%s-%s</b> içerisinde %s gösteriliyor";

$definitions["Warning"] = "Uh oh, bir şey doğru değil!";
$definitions["Welcome to esoTalk!"] = "esoTalk'a hoşgeldiniz!";
$definitions["We've logged you in and taken you straight to your forum's administration panel. You're welcome."] = "Giriş yapıldı ve forum yönetim paneline direk olarak giriş yaptırıldınız..";
$definitions["Write a reply..."] = "Bir cevap yaz...";

$definitions["Yes"] = "Evet";
$definitions["You can manage channel-specific permissions on the channels page."] = "Kanal izinlerini kanal sayfasından yönetebilirsiniz.";
$definitions["Your current password"] = "Şu anki şifreniz";


// Messages.
$definitions["message.404"] = "Ulaşmaya çalıştığınız sayfa bulunamadı! Lütfen farklı bir adres deneyiniz.";
$definitions["message.ajaxDisconnected"] = "Sunucu ile iletişim kurulamadı. Bir kaç saniye bekleyip <a href='javascript:jQuery.ETAjax.resumeAfterDisconnection()'>tekrar deneyin</a>, ya da <a href='' onclick='window.location.reload();return false'>sayfayı yenileyin</a>.";
$definitions["message.ajaxRequestPending"] = "Hey! Hala bazı işlemler tamamlanmadı. Sayfadan ayrılmanız durumunda bazı verileriniz kaybolar. Beklemek ister misiniz?";
$definitions["message.avatarError"] = "Avatar yüklenirken bir hatayla karşılaşıldı. Doğru resim türü kullandığınızdan emin olunuz (örn .jpg, .png, or .gif) ve dosya boyutunun büyük olmadığından emin olunuz.";
$definitions["message.cannotDeleteLastChannel"] = "Son kanalı silemezsiniz! Tartışmaların nereye gitmesini bekliyorsunuz?";
$definitions["message.changesSaved"] = "Değişiklikleriniz kaydedildi.";
$definitions["message.channelsHelp"] = "Kanallar forumunuzu kategorize etmek için kullanılır. Dilediğiniz kadar kanal oluşturup sürükle bırak yaparak iç içe geçirebilirsiniz.";
$definitions["message.channelSlugTaken"] = "Bu kanal urlsi başka kanal tarafından kullanılmaktadır.";
$definitions["message.confirmDelete"] = "Silmek istediğinize emin misiniz? Bu işlemin geri dönüşü yoktur.";
$definitions["message.confirmDiscardReply"] = "Cevabınızı kaydetmediniz. İptal edip çıkmak istediğinize emin misiniz?";
$definitions["message.confirmEmail"] = "Yeni oluşturduğunuz hesabınızı kullanabilmeniz için E-posta adresinizi doğrulamanız gerekmektedir. E-posta adresinize bir kaç dakika içerisinde aktivasyon bağlantısı gönderilecektir.";
$definitions["message.confirmLeave"] = "Değişiklikleri kaydetmediniz! Ayrılmak istediğinize emin misiniz?";
$definitions["message.connectionError"] = "esoTalk MySQL sunucusuna bağlanamıyor. Döndürülen hata:<br/>%s";
$definitions["message.conversationDeleted"] = "Bu tartışma silindi! Yanlış olduğunu mu düşünüyorsunuz?";
$definitions["message.conversationNotFound"] = "Bu tartışma görüntülenemiyor. Var olmayabilir ya da bu tartışmayı görüntelemek için yetkiniz yoktur.";
$definitions["message.cookieAuthenticationTheft"] = "Güvenlik sebeplerinden dolayı 'beni hatırla' çerezi ile giriş yapamıyorsunuz. Lütfen manuel olarak giriş yapınız!";
$definitions["message.deleteChannelHelp"] = "Woah, hold up there! If you delete this channel, there'll be no way to get it back. Unless you build a time machine. But, uh, there'll be no <em>easy</em> way to get it back. All of the conversations in this channel can be moved to another of your choice.";
$definitions["message.emailConfirmed"] = "Harika! Hesabınız doğrulandı ve artık tartışmalara katılabilirsiniz. Neden kendiniz <a href='".URL("conversation/start")."'>yeni bir tartışma</a> başlatmayasınız?";
$definitions["message.emailDoesntExist"] = "Böyle bir eposta adresi bulunamadı. Yazım yanlışı olabilir mi?";
$definitions["message.emailNotYetConfirmed"] = "Giriş yapabilmeniz için eposta adresinizi doğrulamalısınız! Aktivasyon maili gelmediyse, <a href='%s'>buraya tıklayarak yeniden gönderin</a>.";
$definitions["message.emailTaken"] = "Bu e-posta adresiyle kayıtlı başka bir kullanıcı mevcut!";
$definitions["message.empty"] = "Bu alan zorunlu.";
$definitions["message.emptyPost"] = "Boş mesaj gönderemezsiniz.";
$definitions["message.emptyTitle"] = "Tartışma başlığı boş olamaz. Sence boş bir başlığa tıklanabilir mi :)";
$definitions["message.esoTalkAlreadyInstalled"] = "<strong>esoTalk zaten yüklü.</strong><br/><small>Yeniden yüklemek için, <strong>config/config.php</strong> dosyasını silmelisiniz.</small>";
$definitions["message.esoTalkUpdateAvailable"] = "esoTalk, %s yeni versionu mevcut.";
$definitions["message.esoTalkUpdateAvailableHelp"] = "esoTalk güvenliğini arttırmak ve yeni özelliklerden faydalanmanız için yeni versionunu yüklemeniz tavsiye edilir!";
$definitions["message.esoTalkUpToDate"] = "Kullandığınız esoTalk güncel.";
$definitions["message.esoTalkUpToDateHelp"] = "I'm a poor college student who has spent many hundreds of hours developing esoTalk. If you like it, please consider <a href='%s' target='_blank'>donating</a>."; //TODO : translate
$definitions["message.fatalError"] = "<p>esoTalk has encountered an nasty error which is making it impossible to do whatever it is that you're doing. But don't feel down - <strong>here are a few things you can try</strong>:</p>\n<ul>\n<li>Go outside, walk the dog, have a coffee... then <strong><a href='%1\$s'>try again</a></strong>!</li>\n<li>If you are the forum administrator, then you can <strong>get help on the <a href='%2\$s'>esoTalk website</a></strong>.</li>\n<li>Try hitting the computer - that sometimes works for me.</li>\n</ul>";//TODO : translate
$definitions["message.fatalErrorInstaller"] = "<p>esoTalk has encountered an nasty error which is making it impossible to do whatever it is that you're doing. But don't feel down - <strong>here are a few things you can try</strong>:</p>\n<ul>\n<li><p><strong>Try again.</strong> Everyone makes mistakes - maybe the computer made one this time!</p></li>\n<li><p><strong>Go back and check your settings.</strong> In particular, make sure your database information is correct.</p></li>\n<li><p><strong>Get help.</strong> Go on the <a href='%s'>esoTalk support forum</a> and search to see if anyone else is having the same problem as you are. If not, start a new conversation about your problem, including the error details below.</p></li>\n</ul>";//TODO : translate
$definitions["message.fatalErrorUpgrader"] = "<p>esoTalk has encountered an nasty error which is making it impossible to do whatever it is that you're doing. But don't feel down - <strong>here are a few things you can try</strong>:</p>\n<ul>\n<li><p><strong>Try again.</strong> Everyone makes mistakes - maybe the computer made one this time!</p></li>\n<li><p><strong>Get help.</strong> Go on the <a href='%s'>esoTalk support forum</a> and search to see if anyone else is having the same problem as you are. If not, start a new conversation about your problem, including the error details below.</p></li>\n</ul>";//TODO : translate
$definitions["message.fileUploadFailed"] = "Yükleme başarısız. Yüklemeye çalıştığınız dosya formatından ve dosya boyutunun büyük olmadığından emin olunuz?"; 
$definitions["message.fileUploadFailedMove"] = "Dosya kaynağından kopyalanamadı. Lütfen yöneticiyle irtibata geçiniz.";
$definitions["message.fileUploadNotImage"] = "Yüklediğiniz dosya kabul edilen resim formatında değildir.";
$definitions["message.fileUploadTooBig"] = "Seçtiğiniz dosya boyutu çok büyük.";
$definitions["message.forgotPasswordHelp"] = "Şifrenizi mi unuttunuz! Endişelenmeyin her zaman olan şey. E-posta adresinizi girerseniz sıfırlama talimatı size gönderilecektir.";
$definitions["message.fulltextKeywordWarning"] = "Arama yapabilmek için en az 4 karakter girmelisiniz.";
$definitions["message.gambitsHelp"] = "Gambits are phrases that describe what you are looking for. Click on a gambit to insert it into the search field. Double-click on a gambit to instantly search for it. Normal search keywords work too!"; //TODO : translate
$definitions["message.gdNotEnabledWarning"] = "The <strong>GD eklentisi</strong> aktif değil.<br/><small>Bu eklenti resimleri yeniden boyutlandırmak için gereklidir. Lütfen sunucu yöneticinizle irtibata geçiniz.</small>";
$definitions["message.greaterMySQLVersionRequired"] = "<strong>MySQL 4 veya üstü</strong> versionu gerekli. <a href='http://php.net/manual/tr/mysql.installation.php' target='_blank'>MySQL eklentisini PHP yükleme</a>.<br/><small>Sunucu/Host yöneticinizle irtibata geçerek bu yüklemeyi isteyiniz.</small>";
$definitions["message.greaterPHPVersionRequired"] = "esoTalk yüklemek için Sunucuda <strong>PHP 5.0.0 versionu veya üstü</strong> gereklidir.<br/><small>.</small>";
$definitions["message.incorrectLogin"] = "Giriş bilgileriniz hatalıdır.";
$definitions["message.incorrectPassword"] = "Şifreniz hatalı.";
$definitions["message.installerAdminHelp"] = "esoTalk forum yönetici hesabınız oluşturmak için belirtilen bilgileri kullanacaktır.";
$definitions["message.installerFilesNotWritable"] = "esoTalk belirtilen dosya/klasörlere yazamıyor: <strong>%s</strong>.<br/><small>Problemi çözmek için belirtilen klasörlerin <strong>chmod</strong> <strong>777</strong> olarak ayarlamalısınz.</small>";
$definitions["message.installerMySQLHelp"] = "esoTalk, forum verilerini saklamak için MySQL veritabanına ihtiyaç duyar. Sunucu/Host yöneticisi ile irtibata geçiniz.";
$definitions["message.installerWelcome"] = "<p>esoTalk Yükleyicisine hoşgeldiniz! .</p>\n<p>Problem ya da sorularınız için <a href='%s'>esoTalk destek forumu</a>.</p>";
$definitions["message.invalidChannel"] = "Geçersiz bir kanal seçildi!";
$definitions["message.invalidEmail"] = "E-posta adresi doğru gözükmüyor...";
$definitions["message.invalidUsername"] = "Kullanıcı adınız 3-20 karakter arasında alfanümerik olmalıdır.";
$definitions["message.javascriptRequired"] = "Bu sayfada JavaScript gereklidir. Lütfen JavaScripti aktifleştiriniz!";
$definitions["message.languageUninstalled"] = "Dil kaldırıldı.";
$definitions["message.locked"] = "<strong>Kilitli</strong> tartışma, Kilitli tartışmaya cevap yazamazsınız.";
$definitions["message.loginToParticipate"] = "Gönderiyi yanıtlamak için, giriş yapmalısınız.";
$definitions["message.logInToReply"] = "Cevaplamak için <a href='%1\$s' class='link-login'>Giriş yap</a> ya da <a href='%2\$s' class='link-join'>Kayıt Ol</a>!";
$definitions["message.logInToSeeAllConversations"] = "Tüm gönderileri görmek için <a href='".URL("user/login")."' class='link-login'>Giriş yap</a>.";
$definitions["message.memberNotFound"] = "Bu kullanıcı adıyle kayıtlı bir üye bulunamadı.";
$definitions["message.memberNoPermissionView"] = "That member can't be added because they don't have permission to view the channel that this conversation is in.";//TODO : translate
$definitions["message.nameTaken"] = "Girdiğiniz isim sistem tarafından ayrılmış kelimeler arasındadır.";
$definitions["message.newSearchResults"] = "Arama sonuçlarınızı etkileyen yeni bir aktivite olmuştur. <a href='%s'>Yenile</a>";
$definitions["message.noActivity"] = "%s hasn't done anything on this forum yet!"; //TODO : translate
$definitions["message.noMembersOnline"] = "Çevrimiçi kullanıcı yok.";
$definitions["message.noNotifications"] = "Bildiriminiz bulunmamaktadır.";
$definitions["message.noPermission"] = "Bu işlemi yapmak için yetkiniz bulunmamaktadır.";
$definitions["message.noPermissionToReplyInChannel"] = "Bu kanalda tartışmaları cevaplamaya yetkiniz bulunmamaktadır.";
$definitions["message.noPluginsInstalled"] = "Yüklü eklenti bulunamadı.";
$definitions["message.noSearchResults"] = "Arama kriterlerinize uygun tartışma bulunamadı.";
$definitions["message.noSearchResultsMembers"] = "Arama kriterlerinize uygun üye bulunamadı.";
$definitions["message.noSearchResultsPosts"] = "Arama kriterlerinize uygun gönderi bulunamadı.";
$definitions["message.noSkinsInstalled"] = "Yüklü tema bulunamadı.";
$definitions["message.notWritable"] = "<code>%s</code> dosya/klasör ün yazma izni yoktur. <code>chmod</code> ayarını <code>777</code> olarak değiştirin.";
$definitions["message.pageNotFound"] = "Ulaşmaya çalıştığınız sayfa bulunamadı.";
$definitions["message.passwordChanged"] = "Şifreniz başarıyla değiştirildi. Şimdi giriş yapabilirsiniz. Tekrar unutmamaya çalış olur mu ;)";
$definitions["message.passwordEmailSent"] = "Şifre sıfırlama linki eposta adresinize gönderilmiştir.";
$definitions["message.passwordsDontMatch"] = "Şifreleriniz uyuşmuyor.";
$definitions["message.passwordTooShort"] = "Şifreniz çok kısa.";
$definitions["message.pluginCannotBeEnabled"] = "<em>%s</em> eklentisi aktifleştirilemedi: %s";
$definitions["message.pluginDependencyNotMet"] = "Eklentiyi aktifleştirmek için %s version %s yüklü ve aktif olması gerekir.";
$definitions["message.pluginUninstalled"] = "Eklenti kaldırıldı.";
$definitions["message.postNotFound"] = "Ulaşmaya çalıştığınız gönderi bulunamadı.";
$definitions["message.postTooLong"] = "Gönderiniz çok uzun! Gerçekten çok uzun! En fazla %s karaktere izin verilmektedir. Anlaşıldı mı?";
$definitions["message.preInstallErrors"] = "esoTalk yükleme sırasında bazı hatalarla karşılaşıldı. Yüklemeye devam etmek için belirtilen hataları gideriniz.";
$definitions["message.preInstallWarnings"] = "esoTalk yükleme sırasında bazı hatalarla karşılaşıldı. Bu hataları gidermeden yüklemeye devam ederseniz esoTalk un bazı fonksiyonları çalışmayabilir.";
$definitions["message.reduceNumberOfGambits"] = "Reduce the number of gambits or search keywords you're using to find a broader range of conversations."; //TODO : translate
$definitions["message.registerGlobalsWarning"] = "PHP nin <strong>register_globals</strong> ayarı aktiftir.<br/><small>Güvenlik sorunu ve problem yaşamamak için bu özelliği off duruma getiriniz.</small>";
$definitions["message.registrationClosed"] = "Bu forumda üyelik herkese açık değildir.";
$definitions["message.removeDirectoryWarning"] = "Hey! Looks like you haven't deleted the <code>%s</code> directory like we told you to! You probably should, just to make sure those hackers can't do anything naughty."; // TODO : translate
$definitions["message.safeModeWarning"] = "<strong>Safe mode</strong> is enabled.<br/><small>This could potentially cause problems with esoTalk, but you can still proceed if you cannot turn it off.</small>";
$definitions["message.searchAllConversations"] = "Tüm tartışamalar içerisinde arayın.";
$definitions["message.setNewPassword"] = "Tamamdır! Yeni şifrenizin ne olmasını istersiniz?";
$definitions["message.skinUninstalled"] = "Tema kaldırıldı.";
$definitions["message.suspended"] = "Aman tanrım! Bir forum yöneticisi hesabınızı <strong>dondurdu</strong>!";
$definitions["message.suspendMemberHelp"] = "Suspending %s will prevent them from replying to conversations, starting conversations, and viewing private conversations. They will effectively have the same permissions as a guest."; //TODO : translate
$definitions["message.tablePrefixConflict"] = "Yükleyici aynı isimde veritabanı ve aynı ön ekiyle yüklü esoTalk yüklemesi tespit etti.<br/>Yüklemeyi üzerine yazmak için 'Forumu Yükle' e tekrar tıklayınız. <strong>Tüm verileniz kaybolacaktır.</strong><br/>Eğer farklı bir esotTalk forumu yüklemek istiyorsanız lütfen farklı bir tablo <strong>ön ekini</strong> deneyiniz.";
$definitions["message.unsuspendMemberHelp"] = "Unsuspending %s will enable them to participate in conversations on this forum again.";
$definitions["message.upgradeSuccessful"] = "esoTalk was successfully upgraded.";
$definitions["message.waitToReply"] = "Yei bir cevap yazmak için %s saniye beklemelisiniz. Derin bir nefes alın ve tekrar deneyin.";
$definitions["message.waitToSearch"] = "Yavaşla biraz! Yeni bir arama yapmak için %s saniye beklemelisin.";


// Emails.
$definitions["email.confirmEmail.body"] = "<p>Birisi(umarım bu sensin :) bu foruma '%1\$s' bu eposta adresiyle kayıt oldu!.</p><p>Eğer sensen aşağıdaki linke tıklayarak hesabını aktifleştirebilirsin:<br>%2\$s</p>";
$definitions["email.confirmEmail.subject"] = "%1\$s, lütfen eposta adresini doğrula";
$definitions["email.footer"] = "<p>(Daha fazla eposta almak istemiyorsan, <a href='%s'>buradan bildirim ayarlarını</a> değiştirebilirsin.)</p>";
$definitions["email.forgotPassword.body"] = "<p>Şifre sıfırlama '%1\$s'. Şifrenizi sıfırlamak istemiyorsanız bu mesajı göz ardı edin.</p><p>Şifrenizi sıfırlamak için bu bağlantıyı ziyaret edin:<br>%2\$s</p>";
$definitions["email.forgotPassword.subject"] = "Şifreni mi unuttun?, %1\$s?";
$definitions["email.header"] = "<p>Merhaba %s!</p>";
$definitions["email.mention.body"] = "<p><strong>%1\$s</strong> bir konuda senden bahsetti <strong>%2\$s</strong>.</p><hr>%3\$s<hr><p>Görüntülemek için bağlantıyı ziyaret et:<br>%4\$s</p>";
$definitions["email.mention.subject"] = "%1\$s gönderisinde senden bahsetti";
$definitions["email.privateAdd.body"] = "<p>Özel bir tartışmaya eklendiniz. <strong>%1\$s</strong>.</p><hr>%2\$s<hr><p>Görüntülemek için bağlantıyı ziyaret edin:<br>%3\$s</p>";
$definitions["email.privateAdd.subject"] = "Özel bir tartışmaya eklendiniz";
$definitions["email.post.body"] = "<p><strong>%1\$s</strong> Takip ettiğiniz tartışmaya yeni bir cevap yazıldı: <strong>%2\$s</strong></p><hr>%3\$s<hr><p>Görüntülemek için bağlantıyı ziyaret ediniz:<br>%4\$s</p>";
$definitions["email.post.subject"] = "Yeni bir cevap yazıldı: '%1\$s'";


// Translating the gambit system can be quite complex, but we'll do our best to get you through it. :)
// Note: Don't use any html entities in these definitions, except for: &lt; &gt; &amp; &#39;

// Simple gambits
// These gambits are pretty much evaluated as-they-are.
// tag:, author:, contributor:, and quoted: are combined with a value after the colon (:).
// For example: tag:video games, author:myself
$definitions["gambit.author:"] = "author:";
$definitions["gambit.contributor:"] = "contributor:";
$definitions["gambit.member"] = "member";
$definitions["gambit.myself"] = "myself";
$definitions["gambit.draft"] = "draft";
$definitions["gambit.locked"] = "locked";
$definitions["gambit.order by newest"] = "order by newest";
$definitions["gambit.order by replies"] = "order by replies";
$definitions["gambit.private"] = "private";
$definitions["gambit.random"] = "random";
$definitions["gambit.reverse"] = "reverse";
$definitions["gambit.starred"] = "followed";
$definitions["gambit.muted"] = "muted";
$definitions["gambit.sticky"] = "sticky";
$definitions["gambit.unread"] = "unread";
$definitions["gambit.limit:"] = "limit:";

// Aliases
// These are gambits which tell the gambit system to use another gambit.
// In other words, when you type "active today", the gambit system interprets it as if you typed "active 1 day".
// The first of each pair, the alias, can be anything you want.
// The second, however, must fit with the regular expression pattern defined below (more on that later.)
$definitions["gambit.active today"] = "active today"; // what appears in the gambit cloud
$definitions["gambit.active 1 day"] = "active 1 day"; // what it actually evaluates to

$definitions["gambit.has replies"] = "has replies";
$definitions["gambit.has >0 replies"] = "has >0 replies";
$definitions["gambit.has >10 replies"] = "has >10 replies";

$definitions["gambit.has no replies"] = "has no replies";
$definitions["gambit.has 0 replies"] = "has 0 replies";

$definitions["gambit.dead"] = "dead";
$definitions["gambit.active >30 day"] = "active >30 day";

// Units of time
// These are used in the active gambit.
// ex. "[active] [>|<|>=|<=|last] 180 [second|minute|hour|day|week|month|year]"
$definitions["gambit.second"] = "saniye";
$definitions["gambit.minute"] = "dakika";
$definitions["gambit.hour"] = "saat";
$definitions["gambit.day"] = "gün";
$definitions["gambit.week"] = "hafta";
$definitions["gambit.month"] = "ay";
$definitions["gambit.year"] = "yıl";
$definitions["gambit.last"] = "son"; // as in "active last 180 days"
$definitions["gambit.active"] = "aktif"; // as in "active last 180 days"

// Now the hard bit. This is a regular expression to test for the "active" gambit.
// The group (?<a> ... ) is the comparison operator (>, <, >=, <=, or last).
// The group (?<b> ... ) is the number (ex. 24).
// The group (?<c> ... ) is the unit of time.
// The languages of "last" and the units of time are defined above.
// However, if you need to reorder the groups, do so carefully, and make sure spaces are written as " *".
$definitions["gambit.gambitActive"] = "/^{$definitions["gambit.active"]} *(?<a>>|<|>=|<=|{$definitions["gambit.last"]})? *(?<b>\d+) *(?<c>{$definitions["gambit.second"]}|{$definitions["gambit.minute"]}|{$definitions["gambit.hour"]}|{$definitions["gambit.day"]}|{$definitions["gambit.week"]}|{$definitions["gambit.month"]}|{$definitions["gambit.year"]})/";

// These appear in the tag cloud. They must fit the regular expression pattern where the ? is a number.
// If the regular expression pattern has been reordered, these gambits must also be reordered (as well as the ones in aliases.)
$definitions["gambit.active last ? hours"] = "{$definitions["gambit.active"]} {$definitions["gambit.last"]} ? {$definitions["gambit.hour"]}s";
$definitions["gambit.active last ? days"] = "{$definitions["gambit.active"]} {$definitions["gambit.last"]} ? {$definitions["gambit.day"]}s";

// This is similar to the regular expression for the active gambit, but for the "has n reply(s)" gambit.
// Usually you just need to change the "has" and "repl".
$definitions["gambit.gambitHasNReplies"] = "/^has *(?<a>>|<|>=|<=)? *(?<b>\d+) *repl/";