import whisper


class Whisper:
    model = whisper.load_model("small.en")

    @classmethod
    def decode(cls, sound_file):
        result = cls.model.transcribe(sound_file)
        return Whisper.strip(result["text"])

    @staticmethod
    def strip(str):
        return str.replace(".", "").lower().strip()
