import threading


class MetaSingleton(type):
    __instance = None

    def __call__(cls, *args, **kwargs):
        if not cls.__instance:
            cls.__instance = super(MetaSingleton, cls).__call__(*args, **kwargs)
        return cls.__instance


class MetaThreadSingleton(type):
    __instances = {}

    def __call__(cls, *args, **kwargs):
        thread = threading.current_thread()
        thread_name = str(thread)
        instance = cls.__instances.get(thread_name, None)
        if not instance:
            cls.__instances[thread_name] = super(MetaThreadSingleton, cls).__call__(
                *args, **kwargs
            )
        return cls.__instances.get(thread_name)
